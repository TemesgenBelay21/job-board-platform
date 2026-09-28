<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Job;

/**
 * The public home page.
 */
class HomeController extends Controller
{
    public function index()
    {
        $jobModel = new Job();

        $jobs = $jobModel->latest(6);
        $categories = (new Category())->allWithCounts();

        // Only categories with live listings are worth showing as a shortcut.
        $categories = array_values(array_filter($categories, function ($category) {
            return (int) $category['open_job_count'] > 0;
        }));

        echo $this->view('home/index', [
            'title' => 'Find your next role',
            'jobs' => $jobs,
            'categories' => $categories,
            'stats' => [
                'open_jobs' => $jobModel->publicCount(),
                'categories' => count($categories),
            ],
        ]);
    }
}
