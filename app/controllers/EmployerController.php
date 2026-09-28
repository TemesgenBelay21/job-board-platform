<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Validator;
use App\Helpers\Upload;
use App\Models\Application;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\Notification;

/**
 * The employer area: their company profile, their job listings and the
 * applicants for those listings.
 *
 * Every action starts by resolving the signed in employer's company. Listings
 * and applications are then reached through that company id rather than a raw
 * id from the URL, so one employer can never read or change another employer's
 * data even with a correct id. The model methods take the company id as well
 * and apply it inside the WHERE clause, so the check does not rely on a
 * separate lookup having run first.
 */
class EmployerController extends Controller
{
    /** Image types accepted for a company logo. */
    const LOGO_TYPES = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    /** Largest logo we will store, in bytes. */
    const LOGO_MAX_BYTES = 2097152;

    /* ---------------------------------------------------------------------
       Dashboard
       --------------------------------------------------------------------- */

    /**
     * An overview of the employer's listings and applicants.
     */
    public function dashboard()
    {
        $this->requireRole('employer');
        $company = $this->company();
        $jobs = new Job();
        $applications = new Application();

        echo $this->view('employer/dashboard', [
            'title' => 'Dashboard',
            'sidebarTitle' => 'Employer',
            'company' => $company,
            'jobCounts' => [
                'open' => $jobs->countForCompany($company['id'], 'open'),
                'pending' => $jobs->countForCompany($company['id'], 'pending'),
                'closed' => $jobs->countForCompany($company['id'], 'closed'),
            ],
            'applicationCounts' => $applications->countsByStatusForCompany($company['id']),
            'recentApplications' => $applications->paginateForCompany($company['id'], 0, '', 1, 5)['rows'],
            'recentJobs' => $jobs->forCompany($company['id'], null, 5),
        ], 'dashboard');
    }

    /* ---------------------------------------------------------------------
       Job listings
       --------------------------------------------------------------------- */

    /**
     * The employer's own listings, optionally filtered by status.
     */
    public function jobs()
    {
        $this->requireRole('employer');
        $company = $this->company();

        $status = (string) query_param('status', '');
        $jobs = new Job();

        if ($status !== '' && !$jobs->isValidStatus($status)) {
            $status = '';
        }

        echo $this->view('employer/jobs', [
            'title' => 'My job listings',
            'sidebarTitle' => 'Employer',
            'company' => $company,
            'jobs' => $jobs->forCompany($company['id'], $status !== '' ? $status : null),
            'counts' => [
                'open' => $jobs->countForCompany($company['id'], 'open'),
                'pending' => $jobs->countForCompany($company['id'], 'pending'),
                'closed' => $jobs->countForCompany($company['id'], 'closed'),
            ],
            'activeStatus' => $status,
            'statusLabels' => [
                'open' => 'Open',
                'pending' => 'Pending approval',
                'closed' => 'Closed',
            ],
        ], 'dashboard');
    }

    /**
     * The form for posting a new listing.
     */
    public function createJob()
    {
        $this->requireRole('employer');
        $company = $this->company();

        echo $this->view('employer/job-form', [
            'title' => 'Post a job',
            'sidebarTitle' => 'Employer',
            'company' => $company,
            'job' => null,
            'categories' => (new Category())->allWithCounts(),
            'jobTypes' => (new Job())->jobTypes(),
        ], 'dashboard');
    }

    /**
     * Validate and store a new listing.
     *
     * The listing is always created as `pending`; the employer cannot publish
     * it themselves, an admin has to approve it first.
     */
    public function storeJob()
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $validator = $this->validateJob($_POST);
        $categoryId = (int) $validator->value('category_id', 0);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('employer/jobs/create'));
        }

        $values = $this->jobValues($validator, $categoryId);

        try {
            $jobId = (new Job())->post($company['id'], $values);
        } catch (\RuntimeException $exception) {
            flash_error('The listing could not be saved. Please try again.');

            $this->backWithErrors([], url('employer/jobs/create'), false);
        }

        clear_old_input();
        flash_success('Your listing has been submitted and is waiting for approval.');

        $this->redirect('employer/jobs', ['status' => 'pending']);
    }

    /**
     * The form for editing one of the employer's own listings.
     */
    public function editJob($id)
    {
        $this->requireRole('employer');
        $company = $this->company();
        $job = $this->ownedJob($id, $company);

        echo $this->view('employer/job-form', [
            'title' => 'Edit listing',
            'sidebarTitle' => 'Employer',
            'company' => $company,
            'job' => $job,
            'categories' => (new Category())->allWithCounts(),
            'jobTypes' => (new Job())->jobTypes(),
        ], 'dashboard');
    }

    /**
     * Save changes to a listing.
     *
     * Editing a listing that was already public sends it back to `pending`, so
     * the wording an employer sees in search is the wording an admin approved.
     */
    public function updateJob($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $job = $this->ownedJob($id, $company);

        $validator = $this->validateJob($_POST);
        $categoryId = (int) $validator->value('category_id', 0);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('employer/jobs/' . $job['id'] . '/edit'));
        }

        $values = $this->jobValues($validator, $categoryId);
        $model = new Job();

        $model->updateForCompany($job['id'], $company['id'], $values);

        // A live listing that changed goes back for approval. A listing that
        // was pending or closed just stays where it was.
        if ($job['status'] === 'open') {
            $model->setStatus($job['id'], 'pending', $company['id']);
            flash_success('Your changes were saved and the listing is waiting for approval again.');
        } else {
            flash_success('Your listing has been updated.');
        }

        clear_old_input();
        $this->redirect('employer/jobs', ['status' => $job['status'] === 'open' ? 'pending' : $job['status']]);
    }

    /**
     * Close a live listing so it leaves the public search.
     */
    public function closeJob($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $job = $this->ownedJob($id, $company);

        (new Job())->setStatus($job['id'], 'closed', $company['id']);

        flash_success('"' . $job['title'] . '" is now closed and hidden from the job search.');

        $this->redirect('employer/jobs');
    }

    /**
     * Put a closed listing back in the search.
     *
     * A reopened listing goes to `open` only if an admin had already approved
     * it once. Otherwise it returns to `pending`, since a listing that was
     * never approved must not become public by being closed and reopened.
     */
    public function reopenJob($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $job = $this->ownedJob($id, $company);
        $model = new Job();

        if ($job['status'] !== 'closed') {
            flash_info('That listing is not closed.');
        } elseif ($job['approved_at'] !== null) {
            $model->setStatus($job['id'], 'open', $company['id']);
            flash_success('"' . $job['title'] . '" is live again.');
        } else {
            $model->setStatus($job['id'], 'pending', $company['id']);
            flash_success('"' . $job['title'] . '" has been sent for approval.');
        }

        $this->redirect('employer/jobs');
    }

    /**
     * Delete one of the employer's own listings.
     *
     * Deleting removes the applicants' applications with it, so the employer is
     * asked to confirm and is told plainly what will happen.
     */
    public function deleteJob($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $job = $this->ownedJob($id, $company);

        (new Job())->deleteForCompany($job['id'], $company['id']);

        flash_success('"' . $job['title'] . '" has been deleted.');

        $this->redirect('employer/jobs');
    }

    /* ---------------------------------------------------------------------
       Applicants
       --------------------------------------------------------------------- */

    /**
     * The applicants across the employer's listings, or for one listing.
     */
    public function applicants()
    {
        $this->requireRole('employer');
        $company = $this->company();

        $jobId = (int) query_param('job', 0);
        $status = (string) query_param('status', '');
        $page = max(1, (int) query_param('page', 1));

        if ($status !== '' && !isset(Application::STATUS_LABELS[$status])) {
            $status = '';
        }

        // A job id in the query narrows the list, so it has to be one of ours
        // or it is treated as "all jobs" rather than erroring.
        $job = $jobId > 0 ? $this->ownedJobOrNull($jobId, $company) : null;
        $effectiveJobId = $job !== null ? (int) $job['id'] : 0;

        $applications = new Application();

        echo $this->view('employer/applicants', [
            'title' => 'Applicants',
            'sidebarTitle' => 'Employer',
            'company' => $company,
            'results' => $applications->paginateForCompany($company['id'], $effectiveJobId, $status, $page),
            'counts' => $applications->countsByStatusForCompany($company['id']),
            'jobs' => (new Job())->forCompany($company['id']),
            'selectedJob' => $job,
            'activeStatus' => $status,
            'statusLabels' => Application::STATUS_LABELS,
        ], 'dashboard');
    }

    /**
     * Move an applicant to a new status and tell them.
     */
    public function updateApplicationStatus($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $status = (string) input('status', '');

        if (!isset(Application::STATUS_LABELS[$status])) {
            flash_error('That status is not one we recognise.');

            redirect_safe((string) input('return_to', url('employer/applicants')), url('employer/applicants'));
        }

        $applications = new Application();

        if (!$applications->setStatusForCompany((int) $id, $company['id'], $status)) {
            // Either the application is not ours or its status was already this
            // one. Both are reported the same way, so a guessed id learns
            // nothing about another employer's applicant.
            flash_error('That application could not be updated.');

            redirect_safe((string) input('return_to', url('employer/applicants')), url('employer/applicants'));
        }

        $application = $applications->findDetailed((int) $id);

        if ($application !== null) {
            $this->notifyApplicant($application, $status);
        }

        clear_old_input();
        flash_success('The applicant is now marked as ' . strtolower(Application::STATUS_LABELS[$status]) . '.');

        redirect_safe((string) input('return_to', url('employer/applicants')), url('employer/applicants'));
    }

    /* ---------------------------------------------------------------------
       Company profile
       --------------------------------------------------------------------- */

    /**
     * The company profile form.
     */
    public function editCompany()
    {
        $this->requireRole('employer');
        $company = $this->company();

        echo $this->view('employer/company', [
            'title' => 'Company profile',
            'sidebarTitle' => 'Employer',
            'company' => $company,
        ], 'dashboard');
    }

    /**
     * Save the company profile.
     */
    public function updateCompany()
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();

        // The form invites a bare host such as "example.com" while the column
        // stores no scheme, so add one for validation and strip it afterwards.
        $rawWebsite = trim((string) input('website', ''));
        $websiteForValidation = $rawWebsite === '' ? '' : $this->withScheme($rawWebsite);

        $validator = new Validator(array_merge($_POST, ['website' => $websiteForValidation]));

        $validator->validate([
            'company_name' => 'required|min:2|max:150',
            'description' => 'nullable|max:3000',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|max:150',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('employer/company'));
        }

        (new CompanyProfile())->updateDetails($company['id'], [
            'company_name' => $validator->value('company_name'),
            'description' => $this->nullable($validator->value('description')),
            // Strip the scheme so the public page always links somewhere usable
            // and the markup never has to decide between two href shapes.
            'website' => $this->normaliseWebsite($validator->value('website')),
            'location' => $this->nullable($validator->value('location')),
        ]);

        clear_old_input();
        flash_success('Your company profile has been saved.');

        $this->redirect('employer/company');
    }

    /**
     * Store a company logo.
     *
     * The old file is only removed once the new one is safely on disk, so a
     * failed upload cannot leave the company with no logo at all.
     */
    public function uploadLogo()
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();
        $upload = Upload::validate('logo', self::LOGO_TYPES, self::LOGO_MAX_BYTES, true);

        if (!$upload['ok']) {
            flash_error(implode(' ', $upload['errors']));

            redirect_safe((string) input('return_to', url('employer/company')), url('employer/company'));
        }

        $directory = App::publicPath('uploads/logos');
        $previous = !empty($company['logo']) ? $company['logo'] : null;

        try {
            $filename = Upload::store($upload['tmp_name'], $directory, $upload['extension']);
        } catch (\RuntimeException $exception) {
            flash_error('The logo could not be saved. Please try again.');

            redirect_safe((string) input('return_to', url('employer/company')), url('employer/company'));
        }

        $model = new CompanyProfile();
        $model->setLogo($company['id'], $filename);

        if ($previous !== null && $previous !== $filename) {
            $this->deleteLogoFile($previous);
        }

        clear_old_input();
        flash_success('Your logo has been updated.');

        redirect_safe((string) input('return_to', url('employer/company')), url('employer/company'));
    }

    /**
     * Remove the company logo.
     */
    public function deleteLogo()
    {
        $this->requireMethod('POST');
        $this->requireRole('employer');
        $this->verifyCsrf();

        $company = $this->company();

        if (empty($company['logo'])) {
            flash_info('You do not have a logo uploaded.');

            redirect_safe((string) input('return_to', url('employer/company')), url('employer/company'));
        }

        $previous = $company['logo'];

        (new CompanyProfile())->clearLogo($company['id']);
        $this->deleteLogoFile($previous);

        flash_success('Your logo has been removed.');

        redirect_safe((string) input('return_to', url('employer/company')), url('employer/company'));
    }

    /* ---------------------------------------------------------------------
       Helpers
       --------------------------------------------------------------------- */

    /**
     * The signed in employer's company, with the account details the forms
     * need to show.
     */
    private function company()
    {
        $company = (new CompanyProfile())->findWithUser($this->userId());

        if ($company === null) {
            // Registration always creates one, so this only happens if the row
            // was removed underneath the account.
            throw new HttpException(404, 'Your company profile could not be found.');
        }

        return $company;
    }

    /**
     * A listing of this employer's, or 404.
     *
     * The company check is a join on the listing itself, so a job id belonging
     * to someone else is indistinguishable from one that does not exist.
     */
    private function ownedJob($id, $company)
    {
        $job = $this->ownedJobOrNull($id, $company);

        if ($job === null) {
            throw new HttpException(404, 'That job listing could not be found.');
        }

        return $job;
    }

    /** A listing of this employer's, or null. */
    private function ownedJobOrNull($id, $company)
    {
        $job = (new Job())->find((int) $id);

        if ($job === null || (int) $job['company_id'] !== (int) $company['id']) {
            return null;
        }

        return $job;
    }

    /**
     * Shared validation for the create and edit forms.
     *
     * The salary range is checked here rather than in the model because it is a
     * rule about two fields together, and a category that does not exist is
     * rejected the same way.
     */
    private function validateJob(array $input)
    {
        $validator = new Validator($input);

        $validator->validate([
            'title' => 'required|min:4|max:160',
            'description' => 'required|min:20|max:8000',
            'requirements' => 'nullable|max:4000',
            'location' => 'required|min:2|max:150',
            'job_type' => 'required|in:' . implode(',', Job::JOB_TYPES),
            'category_id' => 'nullable|integer',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0',
            'deadline' => 'nullable|date|after_or_equal_today',
        ]);

        $min = $validator->value('salary_min');
        $max = $validator->value('salary_max');

        if ($min !== null && $max !== null && (int) $min > (int) $max) {
            $validator->addError('salary_max', 'The maximum salary cannot be lower than the minimum.');
        }

        $categoryId = (int) $validator->value('category_id', 0);

        if ($categoryId > 0 && (new Category())->find($categoryId) === null) {
            $validator->addError('category_id', 'Choose a category from the list.');
        }

        return $validator;
    }

    /**
     * Turn validated form input into the columns the jobs table expects.
     *
     * Empty numbers and dates become NULL rather than empty strings, so the
     * salary filter and the deadline comparison in public search keep working.
     */
    private function jobValues(Validator $validator, $categoryId)
    {
        $deadline = $this->nullable($validator->value('deadline'));

        return [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'title' => $validator->value('title'),
            'description' => $validator->value('description'),
            'requirements' => $this->nullable($validator->value('requirements')),
            'location' => $validator->value('location'),
            'job_type' => $validator->value('job_type'),
            'salary_min' => $this->nullableInt($validator->value('salary_min')),
            'salary_max' => $this->nullableInt($validator->value('salary_max')),
            'deadline' => $deadline,
        ];
    }

    /**
     * Tell the applicant their application moved.
     *
     * Only the decisions a candidate cares about send a notification, so an
     * employer working through a list of "under review" does not fill the
     * seeker's inbox with noise.
     */
    private function notifyApplicant($application, $status)
    {
        $messages = [
            'shortlisted' => 'You have been shortlisted for "' . $application['title'] . '" at ' . $application['company_name'] . '.',
            'hired' => 'Congratulations, you were hired for "' . $application['title'] . '" at ' . $application['company_name'] . '.',
            'rejected' => 'Your application for "' . $application['title'] . '" at ' . $application['company_name'] . ' was not successful this time.',
        ];

        if (!isset($messages[$status]) || empty($application['seeker_user_id'])) {
            return;
        }

        (new Notification())->notify((int) $application['seeker_user_id'], $messages[$status]);
    }

    /**
     * Remove a stored logo file, if it is really one of ours.
     *
     * The name is sanitised to a bare filename and the path is rebuilt from the
     * uploads directory, so a crafted value cannot reach outside it.
     */
    private function deleteLogoFile($filename)
    {
        $safe = basename((string) $filename);

        if ($safe === '' || strpos($safe, '..') !== false) {
            return;
        }

        $path = App::publicPath('uploads/logos/' . $safe);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Store a website as a bare host, so the public page can always prefix it.
     *
     * A URL that already carries a scheme is left alone apart from lower
     * casing the host, and anything unparseable is dropped rather than stored.
     */
    private function normaliseWebsite($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $parts = parse_url($this->withScheme($value));

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        return strtolower($parts['host']) . (isset($parts['path']) && $parts['path'] !== '/' ? rtrim($parts['path'], '/') : '');
    }

    /** Prefix a bare host with https so it can be parsed or validated. */
    private function withScheme($value)
    {
        return preg_match('~^https?://~i', $value) ? $value : 'https://' . $value;
    }

    /** Empty strings are stored as NULL so the column stays meaningful. */
    private function nullable($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Empty numeric input becomes NULL, otherwise a positive integer. */
    private function nullableInt($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : max(0, (int) $value);
    }
}
