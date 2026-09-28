<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Helpers\Resumes;
use App\Models\Application;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\Notification;
use App\Models\SeekerProfile;
use App\Models\SavedJob;

/**
 * Public job browsing, and applying to a job.
 *
 * Everything here is readable by anyone; only the apply and save actions need
 * a signed in seeker.
 */
class JobController extends Controller
{
    /** Results per page on the browse screen. */
    const PER_PAGE = 10;

    /**
     * The browse page: search, filters, sorting and pagination.
     */
    public function index()
    {
        $filters = [
            'q' => query_param('q'),
            'category' => query_param('category'),
            'job_type' => query_param('job_type'),
            'location' => query_param('location'),
            'salary_min' => query_param('salary_min'),
            'sort' => query_param('sort', Job::DEFAULT_SORT),
            'page' => query_param('page', '1'),
        ];

        // A category that does not exist is dropped rather than returning an
        // unexplained empty page.
        $categories = (new Category())->allWithCounts();
        $validCategoryIds = array_map('intval', array_column($categories, 'id'));

        if ($filters['category'] !== '' && !in_array((int) $filters['category'], $validCategoryIds, true)) {
            $filters['category'] = '';
        }

        $jobModel = new Job();
        $result = $jobModel->searchPublic($filters, self::PER_PAGE);

        // A seeker browsing results needs to see which cards they have already
        // saved or applied to. Both sets come from one query each, not one
        // query per card.
        $savedJobIds = [];
        $appliedJobIds = [];

        if ($this->hasRole('seeker')) {
            $profile = (new SeekerProfile())->findByUser($this->userId());

            if ($profile !== null) {
                $savedJobIds = (new SavedJob())->jobIdsForSeeker($profile['id']);
                $appliedJobIds = (new Application())->appliedJobIds(
                    $profile['id'],
                    array_column($result['rows'], 'id')
                );
            }
        }

        echo $this->view('jobs/index', [
            'title' => 'Browse jobs',
            'results' => $result,
            'filters' => $filters,
            'categories' => $categories,
            'jobTypes' => $jobModel->jobTypes(),
            'sorts' => Job::SORTS,
            'savedJobIds' => $savedJobIds,
            'appliedJobIds' => $appliedJobIds,
        ]);
    }

    /**
     * One job, with the apply form or the seeker's existing application.
     */
    public function show($id)
    {
        $jobModel = new Job();
        $job = $jobModel->findPublic($id);

        if ($job === null) {
            // Fall back to the raw row so an employer and admin can still open
            // their own pending or closed listing, but a stranger gets a 404.
            $job = $jobModel->findWithRelations($id);

            if ($job === null || !$this->mayPreview($job)) {
                throw new \App\Core\HttpException(404, 'That job could not be found.');
            }
        }

        $viewer = $this->viewerContext($job);

        echo $this->view('jobs/show', [
            'title' => $job['title'],
            'job' => $job,
            'category' => $job['category_name'] ?? null,
            'isOpen' => $job['status'] === 'open'
                && ($job['deadline'] === null || strtotime($job['deadline']) >= strtotime('today')),
            'moreJobs' => (new Job())->latest(4),
            'relatedJobs' => $this->relatedJobs($job),
            'application' => $viewer['application'],
            'profile' => $viewer['profile'],
            'savedJobIds' => $viewer['savedJobIds'],
        ]);
    }

    /**
     * Submit an application.
     *
     * The resume is copied from the seeker's profile at the moment of applying,
     * so a later profile change cannot rewrite the history of what an employer
     * actually received. A per application upload is allowed to override it.
     */
    public function apply($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        // A job that is closed, expired or not yet approved cannot be applied to.
        // findPublic() already returns null for all three, and the message stays
        // the same so the response does not reveal that a specific id exists.
        $job = (new Job())->findPublic($id);

        if ($job === null) {
            flash_error('That job is no longer accepting applications.');

            redirect_safe(input_string('return_to', ''), url('jobs'));
        }

        $profile = (new SeekerProfile())->findByUser($this->userId());
        $applications = new Application();
        $returnTo = input_string('return_to', url('jobs/' . (int) $id));

        if ($applications->hasApplied($profile['id'], $job['id'])) {
            flash_info('You have already applied to this job.');

            redirect_safe($returnTo, url('jobs/' . (int) $id));
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'cover_letter' => 'nullable|max:3000',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('jobs/' . (int) $id), false);
        }

        $coverLetter = trim(input_string('cover_letter', ''));
        $resumePath = !empty($profile['resume_path']) ? $profile['resume_path'] : null;

        $hasResume = !empty($profile['resume_path']) && Resumes::exists($profile['resume_path']);

        if (!$hasResume) {
            $this->backWithErrors(
                ['Add a resume to your profile before applying.'],
                url('jobs/' . (int) $id),
                false
            );
        }

        // A seeker can attach a different CV for one application. It is only
        // kept if the application is actually created, so a failed insert does
        // not leave an orphan file behind.
        $uploaded = null;
        $stored = Resumes::storeUpload('resume', false);

        if (!$stored['ok']) {
            $this->backWithErrors($stored['errors'], url('jobs/' . (int) $id), false);
        }

        if (!empty($stored['stored'])) {
            $uploaded = $stored['filename'];
            $resumePath = $uploaded;
        }

        $applicationId = $applications->apply($profile['id'], $job['id'], $coverLetter, $resumePath);

        if ($applicationId === false) {
            if ($uploaded !== null) {
                Resumes::delete($uploaded);
            }

            flash_info('You have already applied to this job.');

            redirect_safe($returnTo, url('jobs/' . (int) $id));
        }

        // Tell the employer somebody applied.
        $employerUserId = (int) $job['employer_user_id'];
        (new Notification())->notify(
            $employerUserId,
            'New application for ' . $job['title']
        );

        clear_old_input();
        flash_success('Your application for ' . $job['title'] . ' has been sent.');

        redirect_safe($returnTo, url('seeker/applications'));
    }

    /**
     * Withdraw an application.
     */
    public function withdraw($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        $profile = (new SeekerProfile())->findByUser($this->userId());

        // The id in the URL is the application id, so the row is fetched by
        // primary key and then checked against this seeker. The join to
        // seeker_profiles is what stops one seeker withdrawing another's
        // application by guessing an id.
        $application = (new Application())->findOwnedBySeeker((int) $id, $profile['id']);

        if ($application === null) {
            throw new \App\Core\HttpException(404, 'That application could not be found.');
        }

        if (!in_array($application['status'], ['submitted', 'under_review'], true)) {
            flash_error('This application can no longer be withdrawn.');

            $this->redirect('seeker/applications');
        }

        (new Application())->delete($application['id']);

        // The per application CV is ours to remove; the profile one is not.
        if (!empty($application['resume_path']) && $application['resume_path'] !== $profile['resume_path']) {
            Resumes::delete($application['resume_path']);
        }

        clear_old_input();
        flash_success('Your application has been withdrawn.');

        redirect_safe(input_string('return_to', ''), url('seeker/applications'));
    }

    /**
     * Can this signed in user preview a listing that is not publicly open?
     *
     * The owner of the listing and admins can; nobody else can.
     */
    private function mayPreview(array $job)
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user['role'] === 'admin') {
            return true;
        }

        return $user['role'] === 'employer'
            && (int) $job['employer_user_id'] === (int) $user['id'];
    }

    /**
     * The signed in seeker's application for this job, plus the ids they have
     * bookmarked, so the page can render the right button without extra
     * queries per card.
     */
    private function viewerContext(array $job)
    {
        $context = ['application' => null, 'profile' => null, 'savedJobIds' => []];

        if (!$this->hasRole('seeker')) {
            return $context;
        }

        $profile = (new SeekerProfile())->findByUser($this->userId());

        if ($profile === null) {
            return $context;
        }

        $context['profile'] = $profile;
        $context['application'] = (new Application())->findForSeekerJob($profile['id'], $job['id']);
        $context['savedJobIds'] = (new SavedJob())->jobIdsForSeeker($profile['id']);

        return $context;
    }

    /** Other listings in the same category, or nearby by date. */
    private function relatedJobs(array $job)
    {
        $model = new Job();

        if (!empty($job['category_id'])) {
            $related = $model->searchPublic(['category' => (int) $job['category_id']], 4);

            if (!empty($related['rows'])) {
                return array_values(array_filter(
                    $related['rows'],
                    function ($row) use ($job) {
                        return (int) $row['id'] !== (int) $job['id'];
                    }
                ));
            }
        }

        return array_values(array_filter(
            $model->latest(5),
            function ($row) use ($job) {
                return (int) $row['id'] !== (int) $job['id'];
            }
        ));
    }
}
