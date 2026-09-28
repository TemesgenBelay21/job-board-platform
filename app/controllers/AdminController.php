<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Validator;
use App\Models\Application;
use App\Models\Category;
use App\Models\Job;
use App\Models\Notification;
use App\Models\SavedJob;
use App\Models\SeekerProfile;
use App\Models\User;

/**
 * The admin area: platform overview, accounts, listing review and categories.
 *
 * An admin is a moderator, not a superuser. Two limits are deliberate:
 *
 *   1. An admin cannot deactivate their own account, so the last admin cannot
 *      be locked out of the area.
 *   2. An admin never sees a CV. Reading an applicant's resume is the
 *      employer's decision to make, and ResumeController has no admin path.
 *
 * Every action requires the admin role, and the role check is in the
 * controller rather than the router so a guessed URL cannot reach it.
 */
class AdminController extends Controller
{
    /* ---------------------------------------------------------------------
       Overview
       --------------------------------------------------------------------- */

    /**
     * Platform totals, the review queue and the newest accounts.
     */
    public function dashboard()
    {
        $this->requireRole('admin');

        $users = new User();
        $jobs = new Job();

        echo $this->view('admin/dashboard', [
            'title' => 'Overview',
            'sidebarTitle' => 'Administrator',
            'userCounts' => [
                'seeker' => $users->countByRole('seeker'),
                'employer' => $users->countByRole('employer'),
                'admin' => $users->countByRole('admin'),
                'inactive' => $users->countByStatus('inactive'),
            ],
            'jobCounts' => [
                'open' => $jobs->count('`status` = ?', ['open']),
                'pending' => $jobs->count('`status` = ?', ['pending']),
                'closed' => $jobs->count('`status` = ?', ['closed']),
            ],
            'applicationTotal' => (new Application())->count(),
            'pendingReview' => $jobs->paginateAll('pending', 1, 5)['rows'],
            'pendingReviewTotal' => $jobs->count('`status` = ?', ['pending']),
            'recentUsers' => $users->recent(5),
        ], 'dashboard');
    }

    /* ---------------------------------------------------------------------
       Accounts
       --------------------------------------------------------------------- */

    /**
     * Accounts, searchable and filterable by role and status.
     */
    public function users()
    {
        $this->requireRole('admin');

        $users = new User();
        $role = (string) query_param('role', '');
        $status = (string) query_param('status', '');
        $keyword = trim((string) query_param('q', ''));
        $page = max(1, (int) query_param('page', 1));

        // Unknown values are dropped rather than passed through, so the
        // filters can only ever be ones the model recognises.
        if (!in_array($role, User::ROLES, true)) {
            $role = '';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        echo $this->view('admin/users', [
            'title' => 'Users',
            'sidebarTitle' => 'Administrator',
            'results' => $users->search([
                'role' => $role,
                'status' => $status,
                'q' => $keyword,
                'page' => $page,
                'per_page' => 20,
            ]),
            'activeRole' => $role,
            'activeStatus' => $status,
            'keyword' => $keyword,
            'roles' => User::ROLES,
            // The list marks the signed in admin's own row and withholds the
            // deactivate control from it.
            'currentUserId' => $this->userId(),
        ], 'dashboard');
    }

    /**
     * Activate or deactivate an account.
     *
     * Refuses to act on the signed in admin's own account, and reports that
     * plainly rather than failing silently.
     */
    public function updateUserStatus($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('admin');
        $this->verifyCsrf();

        $status = (string) input('status', '');
        $userId = (int) $id;
        $returnTo = $this->returnTarget('admin/users');

        if (!in_array($status, ['active', 'inactive'], true)) {
            flash_error('That status is not one we recognise.');

            redirect_safe($returnTo, url('admin/users'));
        }

        if ($userId === $this->userId()) {
            flash_error('You cannot change the status of your own account.');

            redirect_safe($returnTo, url('admin/users'));
        }

        $user = (new User())->find($userId);

        if ($user === null) {
            flash_error('That account could not be found.');

            redirect_safe($returnTo, url('admin/users'));
        }

        // Deactivating an employer would hide their live listings and lock them
        // out of the applicants waiting for them, so it is surfaced first.
        if ($status === 'inactive' && $user['role'] === 'employer') {
            $live = $this->liveListingCount($userId);

            if ($live > 0) {
                flash_error(
                    'That employer still has ' . $live . ' live listing' . ($live === 1 ? '' : 's')
                    . '. Close or delete them first so candidates are not left waiting.'
                );

                redirect_safe($returnTo, url('admin/users'));
            }
        }

        (new User())->setStatus($userId, $status);

        $verb = $status === 'active' ? 'reactivated' : 'deactivated';

        (new Notification())->notify(
            $userId,
            $status === 'active'
                ? 'Your JobBoard account has been reactivated.'
                : 'Your JobBoard account has been deactivated. Contact an administrator if this is unexpected.'
        );

        clear_old_input();
        flash_success($user['name'] . "'s account has been " . $verb . '.');

        redirect_safe($returnTo, url('admin/users'));
    }

    /* ---------------------------------------------------------------------
       Listing review
       --------------------------------------------------------------------- */

    /**
     * Every listing, with a status filter. The review queue is `pending`.
     */
    public function jobs()
    {
        $this->requireRole('admin');

        $status = (string) query_param('status', '');
        $page = max(1, (int) query_param('page', 1));
        $jobs = new Job();

        if ($status !== '' && !$jobs->isValidStatus($status)) {
            $status = '';
        }

        echo $this->view('admin/jobs', [
            'title' => 'Job listings',
            'sidebarTitle' => 'Administrator',
            'results' => $jobs->paginateAll($status !== '' ? $status : null, $page, 20),
            'counts' => [
                'all' => $jobs->count(),
                'open' => $jobs->count('`status` = ?', ['open']),
                'pending' => $jobs->count('`status` = ?', ['pending']),
                'closed' => $jobs->count('`status` = ?', ['closed']),
            ],
            'activeStatus' => $status,
        ], 'dashboard');
    }

    /**
     * Approve, close or reopen a listing.
     *
     * This is the only way a listing reaches the public search. The first
     * approval stamps `approved_at`, which is what later lets the employer
     * reopen a closed listing without another review.
     */
    public function updateJobStatus($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('admin');
        $this->verifyCsrf();

        $status = (string) input('status', '');
        $jobId = (int) $id;
        $returnTo = $this->returnTarget('admin/jobs');

        if (!in_array($status, ['open', 'closed'], true)) {
            flash_error('A listing can only be approved or closed from here.');

            redirect_safe($returnTo, url('admin/jobs'));
        }

        $job = (new Job())->find($jobId);

        if ($job === null) {
            flash_error('That listing could not be found.');

            redirect_safe($returnTo, url('admin/jobs'));
        }

        $model = new Job();

        if ($status === 'open') {
            $this->approve($job);
        } else {
            $model->setStatus($jobId, 'closed');
            $this->notifyEmployer($job, 'Your listing "' . $job['title'] . '" has been closed by an administrator.');
        }

        clear_old_input();
        flash_success(
            $status === 'open'
                ? '"' . $job['title'] . '" is approved and now visible in the job search.'
                : '"' . $job['title'] . '" has been closed.'
        );

        redirect_safe($returnTo, url('admin/jobs'));
    }

    /* ---------------------------------------------------------------------
       Categories
       --------------------------------------------------------------------- */

    /**
     * Category management.
     */
    public function categories()
    {
        $this->requireRole('admin');

        echo $this->view('admin/categories', [
            'title' => 'Categories',
            'sidebarTitle' => 'Administrator',
            'categories' => (new Category())->allWithCounts(),
        ], 'dashboard');
    }

    /**
     * Create a category.
     */
    public function storeCategory()
    {
        $this->requireMethod('POST');
        $this->requireRole('admin');
        $this->verifyCsrf();

        $name = trim((string) input('name', ''));

        if ($name === '' || mb_strlen($name) > 80) {
            flash_error('Enter a category name of 80 characters or fewer.');

            redirect_safe($this->returnTarget('admin/categories'), url('admin/categories'));
        }

        $categories = new Category();

        if ($categories->nameExists($name)) {
            flash_error('A category called "' . $name . '" already exists.');

            redirect_safe($this->returnTarget('admin/categories'), url('admin/categories'));
        }

        $categories->create(['name' => $name]);

        clear_old_input();
        flash_success('"' . $name . '" has been added.');

        redirect_safe($this->returnTarget('admin/categories'), url('admin/categories'));
    }

    /**
     * Rename a category.
     */
    public function updateCategory($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('admin');
        $this->verifyCsrf();

        $categoryId = (int) $id;
        $name = trim((string) input('name', ''));
        $categories = new Category();
        $returnTo = $this->returnTarget('admin/categories');

        if ($categories->find($categoryId) === null) {
            flash_error('That category could not be found.');

            redirect_safe($returnTo, url('admin/categories'));
        }

        if ($name === '' || mb_strlen($name) > 80) {
            flash_error('Enter a category name of 80 characters or fewer.');

            redirect_safe($returnTo, url('admin/categories'));
        }

        if ($categories->nameExists($name, $categoryId)) {
            flash_error('A category called "' . $name . '" already exists.');

            redirect_safe($returnTo, url('admin/categories'));
        }

        $categories->update($categoryId, ['name' => $name]);

        clear_old_input();
        flash_success('The category has been renamed to "' . $name . '".');

        redirect_safe($returnTo, url('admin/categories'));
    }

    /**
     * Delete a category, but only when no listing still points at it.
     *
     * Deleting a used category would leave live listings uncategorised, so the
     * job count is reported and the delete refused.
     */
    public function deleteCategory($id)
    {
        $this->requireMethod('POST');
        $this->requireRole('admin');
        $this->verifyCsrf();

        $categoryId = (int) $id;
        $categories = new Category();
        $returnTo = $this->returnTarget('admin/categories');

        if ($categories->find($categoryId) === null) {
            flash_error('That category could not be found.');

            redirect_safe($returnTo, url('admin/categories'));
        }

        $result = $categories->deleteIfUnused($categoryId);

        if (!$result['deleted']) {
            flash_error(
                'That category is used by ' . $result['jobs'] . ' listing'
                . ($result['jobs'] === 1 ? '' : 's') . '. Move them to another category first.'
            );

            redirect_safe($returnTo, url('admin/categories'));
        }

        clear_old_input();
        flash_success('The category has been deleted.');

        redirect_safe($returnTo, url('admin/categories'));
    }

    /* ---------------------------------------------------------------------
       Helpers
       --------------------------------------------------------------------- */

    /**
     * Approve a listing and stamp the approval time.
     *
     * The stamp is only written on the transition into `open`, so reopening a
     * listing later does not reset the record of when it was first approved.
     */
    private function approve($job)
    {
        $model = new Job();
        $model->setStatus((int) $job['id'], 'open');

        if (empty($job['approved_at'])) {
            Database::execute(
                'UPDATE `jobs` SET `approved_at` = NOW() WHERE `id` = ?',
                [(int) $job['id']]
            );
        }

        $this->notifyEmployer($job, 'Your listing "' . $job['title'] . '" has been approved and is now live.');
    }

    /**
     * Tell the employer who posted a listing what an admin decided about it.
     *
     * The notification goes to the account, not the company, because the
     * company profile has no notification address of its own.
     */
    private function notifyEmployer($job, $message)
    {
        $employerUserId = (int) Database::fetchColumn(
            'SELECT c.`user_id` FROM `company_profiles` c WHERE c.`id` = ? LIMIT 1',
            [(int) $job['company_id']]
        );

        if ($employerUserId > 0) {
            (new Notification())->notify($employerUserId, $message);
        }
    }

    /** How many live listings an employer still has. */
    private function liveListingCount($userId)
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*)
               FROM `jobs` j
               INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
              WHERE c.`user_id` = ? AND j.`status` = ?',
            [(int) $userId, 'open']
        );
    }

    /**
     * Where to send the admin back to after a POST.
     *
     * The candidate is only honoured when it is one of our own URLs, so a
     * crafted return_to cannot bounce an admin somewhere off-site.
     */
    private function returnTarget($fallback)
    {
        $returnTo = (string) input('return_to', url($fallback));

        return is_own_url($returnTo) ? $returnTo : url($fallback);
    }
}
