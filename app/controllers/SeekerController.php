<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Helpers\Resumes;
use App\Models\Application;
use App\Models\SeekerProfile;
use App\Models\SavedJob;

/**
 * The seeker's own area: profile, resume and their own application history.
 */
class SeekerController extends Controller
{
    /**
     * Show the profile form and a summary of the seeker's activity.
     */
    public function profile()
    {
        $this->requireRole('seeker');

        $profile = $this->profileForUser();
        $applications = new Application();
        $saved = new SavedJob();

        echo $this->view('seeker/profile', [
            'title' => 'My profile',
            'sidebarTitle' => 'Job seeker',
            'profile' => $profile,
            'skills' => (new SeekerProfile())->skills($profile),
            'statusCounts' => $applications->statusCounts($profile['id']),
            'applicationCount' => $applications->count('`seeker_id` = ?', [$profile['id']]),
            'savedCount' => $saved->countForSeeker($profile['id']),
            'resumeName' => $this->resumeName($profile),
        ], 'dashboard');
    }

    /**
     * Save the profile details.
     */
    public function updateProfile()
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        $profile = $this->profileForUser();

        $validator = new Validator($_POST);
        $validator->validate([
            'name' => 'required|min:2|max:120',
            'phone' => 'nullable|max:40',
            'location' => 'nullable|max:150',
            'headline' => 'nullable|max:160',
            'skills' => 'nullable|max:500',
            'bio' => 'nullable|max:2000',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errorMessages(), url('profile'));
        }

        // The account name lives on users, the rest on the profile.
        (new \App\Models\User())->updateName($profile['user_id'], $validator->value('name'));

        $model = new SeekerProfile();
        $model->update($profile['id'], [
            'phone' => $this->nullable($validator->value('phone')),
            'location' => $this->nullable($validator->value('location')),
            'headline' => $this->nullable($validator->value('headline')),
            'skills' => $this->nullable($validator->value('skills')),
            'bio' => $this->nullable($validator->value('bio')),
        ]);

        clear_old_input();
        flash_success('Your profile has been saved.');

        $this->redirect('profile');
    }

    /**
     * Replace the resume on the profile.
     *
     * The old file is only deleted once the new one is safely stored, so a
     * failed upload cannot leave the seeker with no resume at all.
     */
    public function uploadResume()
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        $profile = $this->profileForUser();
        $stored = Resumes::storeUpload('resume', true);

        if (!$stored['ok']) {
            $this->backWithErrors($stored['errors'], url('profile'));
        }

        $previous = $profile['resume_path'];

        (new SeekerProfile())->setResume($profile['id'], $stored['filename']);

        if (!empty($previous) && $previous !== $stored['filename']) {
            Resumes::delete($previous);
        }

        clear_old_input();
        flash_success('Your resume has been uploaded.');

        $this->redirect('profile');
    }

    /**
     * Delete the resume from the profile.
     */
    public function deleteResume()
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        $profile = $this->profileForUser();

        if (empty($profile['resume_path'])) {
            flash_info('You do not have a resume uploaded.');

            $this->redirect('profile');
        }

        (new SeekerProfile())->setResume($profile['id'], null);
        Resumes::delete($profile['resume_path']);

        clear_old_input();
        flash_success('Your resume has been removed.');

        $this->redirect('profile');
    }

    /**
     * The seeker's own applications, newest first.
     */
    public function applications()
    {
        $this->requireRole('seeker');

        $profile = $this->profileForUser();
        $status = query_param('status', '');
        $applications = new Application();

        $counts = $applications->statusCounts($profile['id']);

        echo $this->view('seeker/applications', [
            'title' => 'My applications',
            'sidebarTitle' => 'Job seeker',
            'applications' => $applications->forSeeker($profile['id'], $status !== '' ? $status : null),
            'statusCounts' => $counts,
            'activeStatus' => $status,
            'statusLabels' => Application::STATUS_LABELS,
        ], 'dashboard');
    }

    /**
     * The seeker's saved jobs.
     */
    public function savedJobs()
    {
        $this->requireRole('seeker');

        $profile = $this->profileForUser();
        $saved = new SavedJob();

        echo $this->view('seeker/saved-jobs', [
            'title' => 'Saved jobs',
            'sidebarTitle' => 'Job seeker',
            'jobs' => $saved->forSeeker($profile['id']),
            'savedCount' => $saved->countForSeeker($profile['id']),
        ], 'dashboard');
    }

    /**
     * Save or unsave a job, then return to wherever the button was.
     *
     * The button posts rather than linking, so saving a job cannot be triggered
     * by a crawler or by a prefetching browser.
     */
    public function toggleSavedJob()
    {
        $this->requireMethod('POST');
        $this->requireRole('seeker');
        $this->verifyCsrf();

        $jobId = (int) input('job_id', 0);
        $profile = $this->profileForUser();
        $saved = new SavedJob();
        $returnTo = (string) input('return_to', url('seeker/saved-jobs'));

        if ($jobId <= 0) {
            flash_error('That job could not be found.');

            redirect_safe($returnTo, url('seeker/saved-jobs'));
        }

        $job = (new \App\Models\Job())->findPublic($jobId);

        if ($job === null) {
            flash_error('That job is no longer available.');

            redirect_safe($returnTo, url('seeker/saved-jobs'));
        }

        if ($saved->findForSeekerJob($profile['id'], $jobId) !== null) {
            $saved->remove($profile['id'], $jobId);
            flash_info('Removed "' . $job['title'] . '" from your saved jobs.');
        } else {
            $saved->save($profile['id'], $jobId);
            flash_success('Saved "' . $job['title'] . '".');
        }

        clear_old_input();
        redirect_safe($returnTo, url('seeker/saved-jobs'));
    }

    /**
     * The signed in seeker's profile row, joined with their account so the
     * form can show the name and email the employers will see.
     */
    private function profileForUser()
    {
        $profile = (new SeekerProfile())->findWithUser($this->userId());

        if ($profile === null) {
            // Registration always creates one, so this only happens if the row
            // was removed underneath the account.
            throw new \App\Core\HttpException(404, 'Your profile could not be found.');
        }

        return $profile;
    }

    /** Presentable file name for the stored resume, if there is one. */
    private function resumeName($profile)
    {
        if (empty($profile['resume_path'])) {
            return null;
        }

        return Resumes::exists($profile['resume_path'])
            ? $profile['resume_path']
            : null;
    }

    /** Empty strings are stored as NULL so the column stays meaningful. */
    private function nullable($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
