<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\SeekerProfile;

/**
 * Public company pages: what a candidate wants to know before applying.
 */
class CompanyController extends Controller
{
    /**
     * A company's public profile with its open listings.
     */
    public function show($id)
    {
        $company = (new CompanyProfile())->findPublic($id);

        if ($company === null) {
            throw new \App\Core\HttpException(404, 'That company could not be found.');
        }

        $jobModel = new Job();
        $jobs = $jobModel->openForCompany((int) $company['id'], 20);

        // Let a signed in seeker see which of these they already saved or
        // applied to, with one query each rather than one per card.
        $savedJobIds = [];
        $appliedJobIds = [];

        if ($this->hasRole('seeker')) {
            $profile = (new SeekerProfile())->findByUser($this->userId());

            if ($profile !== null) {
                $savedJobIds = (new SavedJob())->jobIdsForSeeker($profile['id']);
                $appliedJobIds = (new Application())->appliedJobIds(
                    $profile['id'],
                    array_column($jobs, 'id')
                );
            }
        }

        echo $this->view('companies/show', [
            'title' => $company['company_name'],
            'company' => $company,
            'jobs' => $jobs,
            'jobCount' => $jobModel->publicCountForCompany((int) $company['id']),
            'savedJobIds' => $savedJobIds,
            'appliedJobIds' => $appliedJobIds,
        ]);
    }
}
