<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Resumes;
use App\Models\Application;
use App\Models\SeekerProfile;

/**
 * Resume downloads.
 *
 * Resumes are private documents, so every download is authorised here rather
 * than in the views. The rules are:
 *
 *   seeker   - their own profile resume, or the resume attached to one of
 *              their own applications
 *   employer - only the resume on an application for one of their own jobs,
 *              and only while that application exists
 *   admin    - no access: administration does not need to read CVs
 *
 * A seeker id, job id or application id alone is never enough; the ownership
 * condition is always part of the same query.
 */
class ResumeController extends Controller
{
    /** The seeker's own resume from their profile. */
    public function own()
    {
        $this->requireRole('seeker');

        // Joined with the account so the download can carry a recognisable name
        // rather than the random filename on disk.
        $profile = (new SeekerProfile())->findWithUser($this->userId());

        if ($profile === null || empty($profile['resume_path'])) {
            throw new \App\Core\HttpException(404, 'You have not uploaded a resume yet.');
        }

        $this->send($profile['resume_path'], $this->downloadName($profile));
    }

    /**
     * The resume attached to an application, for the employer who received it.
     */
    public function forApplication($id)
    {
        $this->requireRole('employer');

        $company = (new \App\Models\CompanyProfile())->findByUser($this->userId());

        if ($company === null) {
            throw new \App\Core\HttpException(404, 'Your company profile could not be found.');
        }

        $application = (new Application())->findDetailed($id);

        // The application has to exist and belong to a job of this company.
        if ($application === null || (int) $application['company_id'] !== (int) $company['id']) {
            throw new \App\Core\HttpException(404, 'That application could not be found.');
        }

        // Prefer the copy taken at the moment of applying, falling back to the
        // seeker's current profile resume if the application has none.
        $path = !empty($application['resume_path'])
            ? $application['resume_path']
            : $application['profile_resume_path'];

        if (empty($path)) {
            throw new \App\Core\HttpException(404, 'This applicant has not uploaded a resume.');
        }

        $this->send($path, $this->downloadName($application));
    }

    /**
     * Stream a stored resume, refusing silently on a file we no longer have.
     */
    private function send($path, $downloadName)
    {
        if (!Resumes::exists($path)) {
            throw new \App\Core\HttpException(404, 'That file is no longer available.');
        }

        Resumes::download($path, $downloadName);
    }

    /**
     * A name the employer recognises, e.g. "Sara-Bekele-resume.pdf".
     *
     * The stored filename is random, so it is never used as the visible name.
     */
    private function downloadName($row)
    {
        $name = !empty($row['seeker_name'])
            ? $row['seeker_name']
            : (isset($row['name']) ? $row['name'] : 'resume');

        $slug = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', trim((string) $name)), '-');

        if ($slug === '') {
            $slug = 'resume';
        }

        $extension = Resumes::extension($row['resume_path'] ?? '');

        return $slug . '-resume.' . ($extension !== '' ? $extension : 'pdf');
    }
}
