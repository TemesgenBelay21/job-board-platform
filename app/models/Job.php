<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Job listings.
 *
 * The public listing query needs the company name and category name, so most
 * reads go through joinListings() rather than the base Model helpers.
 */
class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'company_id',
        'category_id',
        'title',
        'description',
        'requirements',
        'location',
        'job_type',
        'salary_min',
        'salary_max',
        'deadline',
        'status',
    ];

    /** Job types a listing may use. */
    const JOB_TYPES = ['full_time', 'part_time', 'contract', 'internship', 'remote'];

    const STATUSES = ['pending', 'open', 'closed'];

    /**
     * Columns every listing view needs, including the company name and logo.
     *
     * Listed out rather than j.* so the joined tables' id, created_at and
     * updated_at columns can never shadow the listing's own.
     */
    const LISTING_COLUMNS = '
        j.`id`,
        j.`company_id`,
        j.`category_id`,
        j.`title`,
        j.`description`,
        j.`requirements`,
        j.`location`,
        j.`job_type`,
        j.`salary_min`,
        j.`salary_max`,
        j.`deadline`,
        j.`status`,
        j.`approved_at`,
        j.`created_at`,
        j.`updated_at`,
        c.`company_name`,
        c.`logo` AS `company_logo`,
        c.`user_id` AS `employer_user_id`,
        cat.`name` AS `category_name`
    ';

    const LISTING_JOINS = '
        FROM `jobs` j
        INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
        LEFT JOIN `categories` cat ON cat.`id` = j.`category_id`
    ';

    public function jobTypes()
    {
        return self::JOB_TYPES;
    }

    public function statuses()
    {
        return self::STATUSES;
    }

    public function isValidType($type)
    {
        return in_array($type, self::JOB_TYPES, true);
    }

    public function isValidStatus($status)
    {
        return in_array($status, self::STATUSES, true);
    }

    /**
     * Find one listing with its company and category joined in.
     */
    public function findWithRelations($id)
    {
        return Database::fetch(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . ' WHERE j.`id` = ? LIMIT 1',
            [(int) $id]
        );
    }

    /**
     * A listing as the public sees it: open, and its deadline has not passed.
     *
     * A listing stays browsable until the day after its deadline so a
     * time-limited job does not disappear before the day it closes.
     */
    public function findPublic($id)
    {
        return Database::fetch(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . "
             WHERE j.`id` = ?
               AND j.`status` = 'open'
               AND (j.`deadline` IS NULL OR j.`deadline` >= CURDATE())
             LIMIT 1",
            [(int) $id]
        );
    }

    /**
     * Total number of live public listings, for the home page and stats.
     */
    public function publicCount($categoryId = null)
    {
        $sql = "SELECT COUNT(*)
                FROM `jobs` j
                WHERE j.`status` = 'open'
                  AND (j.`deadline` IS NULL OR j.`deadline` >= CURDATE())";

        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND j.`category_id` = ?';
            $params[] = (int) $categoryId;
        }

        return (int) Database::fetchColumn($sql, $params);
    }

    /**
     * How many live listings a company has, for its public page.
     */
    public function publicCountForCompany($companyId)
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*)
             FROM `jobs` j
             WHERE j.`company_id` = ?
               AND j.`status` = 'open'
               AND (j.`deadline` IS NULL OR j.`deadline` >= CURDATE())",
            [(int) $companyId]
        );
    }

    /**
     * The newest live listings, for the home page.
     */
    public function latest($limit = 6)
    {
        return Database::fetchAll(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . "
             WHERE j.`status` = 'open'
               AND (j.`deadline` IS NULL OR j.`deadline` >= CURDATE())
             ORDER BY j.`created_at` DESC, j.`id` DESC
             LIMIT " . (int) $limit
        );
    }

    /**
     * Every open listing belonging to one company, newest first.
     */
    public function openForCompany($companyId, $limit = 50)
    {
        return Database::fetchAll(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . "
             WHERE j.`company_id` = ?
               AND j.`status` = 'open'
               AND (j.`deadline` IS NULL OR j.`deadline` >= CURDATE())
             ORDER BY j.`created_at` DESC, j.`id` DESC
             LIMIT " . (int) $limit,
            [(int) $companyId]
        );
    }

    /**
     * All listings owned by a company, whatever their status. Used by the
     * employer dashboard and job management table.
     */
    /**
     * A company's own listings, joined with the category name.
     *
     * @param int         $companyId
     * @param string|null $status  Restrict to one status, or null for all
     * @param int         $limit   0 for every listing
     */
    public function forCompany($companyId, $status = null, $limit = 0)
    {
        $sql = 'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . ' WHERE j.`company_id` = ?';
        $params = [(int) $companyId];

        if ($status !== null) {
            $sql .= ' AND j.`status` = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY j.`created_at` DESC, j.`id` DESC';

        // The limit is cast to an int, never bound, so it cannot carry SQL.
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return Database::fetchAll($sql, $params);
    }

    /** Count listings a company owns, optionally for one status. */
    public function countForCompany($companyId, $status = null)
    {
        $sql = 'SELECT COUNT(*) FROM `jobs` WHERE `company_id` = ?';
        $params = [(int) $companyId];

        if ($status !== null) {
            $sql .= ' AND `status` = ?';
            $params[] = $status;
        }

        return (int) Database::fetchColumn($sql, $params);
    }

    /** Does this listing belong to the given employer user? */
    public function belongsToUser($jobId, $userId)
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*)
             FROM `jobs` j
             INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
             WHERE j.`id` = ? AND c.`user_id` = ?',
            [(int) $jobId, (int) $userId]
        ) > 0;
    }

    /**
     * Set the status of a listing, e.g. when reopening or closing it.
     *
     * @param int    $jobId
     * @param string $status
     * @param int    $companyId Restrict to one company's listing, so an
     *                         employer cannot move a job that is not theirs
     *
     * @return bool True when a row was updated
     */
    public function setStatus($jobId, $status, $companyId = null)
    {
        if (!$this->isValidStatus($status)) {
            throw new \InvalidArgumentException('Unknown job status: ' . $status);
        }

        if ($companyId === null) {
            return $this->update((int) $jobId, ['status' => $status]);
        }

        return Database::execute(
            'UPDATE `jobs` SET `status` = ? WHERE `id` = ? AND `company_id` = ?',
            [$status, (int) $jobId, (int) $companyId]
        ) > 0;
    }

    /**
     * Post a new listing.
     *
     * A new listing always starts as `pending`, whatever the caller asks for:
     * only an admin may publish a job, and the override here is what stops an
     * employer from posting straight to the public search.
     *
     * @return int The new job id
     */
    public function post($companyId, array $values)
    {
        $values['company_id'] = (int) $companyId;
        $values['status'] = 'pending';

        return $this->create($values);
    }

    /**
     * Edit a listing, keeping it owned by the given company.
     *
     * `status` and `approved_at` are deliberately not editable here. A
     * substantive edit sends the listing back for approval (see the controller)
     * rather than letting the employer decide its own visibility, and the
     * approval stamp belongs to the admin action that set it.
     *
     * Returns true when the statement ran, not when a value actually changed:
     * MySQL reports zero affected rows when the new values match the old ones,
     * which is a successful no-op here and not a failure. Use belongsToUser()
     * beforehand when the distinction matters.
     *
     * @param int   $jobId
     * @param int   $companyId
     * @param array $values
     *
     * @return bool True when the row was the company's own
     */
    public function updateForCompany($jobId, $companyId, array $values)
    {
        unset($values['status'], $values['approved_at'], $values['company_id'], $values['id']);

        if ($values === []) {
            return false;
        }

        $set = implode(', ', array_map(function ($column) {
            return '`' . $column . '` = ?';
        }, array_keys($values)));

        $params = array_values($values);
        $params[] = (int) $jobId;
        $params[] = (int) $companyId;

        // Affected row count, not proof of a match. MySQL reports 0 when the
        // values are unchanged, and also 0 when the id or company does not
        // exist, so a caller must check ownership before relying on this.
        return Database::execute(
            'UPDATE `jobs` SET ' . $set . ' WHERE `id` = ? AND `company_id` = ?',
            $params
        ) > 0;
    }

    /**
     * Delete a listing, but only while it is still a company's own.
     *
     * @return bool True when a row was removed
     */
    public function deleteForCompany($jobId, $companyId)
    {
        return Database::execute(
            'DELETE FROM `jobs` WHERE `id` = ? AND `company_id` = ?',
            [(int) $jobId, (int) $companyId]
        ) > 0;
    }

    /** Sort orders offered on the browse page. */
    const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'title' => 'Job title A-Z',
        'salary' => 'Highest salary',
    ];

    const DEFAULT_SORT = 'newest';

    public static function isValidSort($sort)
    {
        return array_key_exists($sort, self::SORTS);
    }

    /**
     * The public browse search: keyword, category, type, location, salary and
     * pagination, with a matching total for the pager.
     *
     * Every value is bound, and only recognised sort keys reach the ORDER BY,
     * so nothing from the query string can alter the shape of the statement.
     *
     * @param array $filters q, category, job_type, location, salary_min, sort
     *
     * @return array{rows: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function searchPublic(array $filters = [], $perPage = 10)
    {
        $where = ["j.`status` = 'open'", '(j.`deadline` IS NULL OR j.`deadline` >= CURDATE())'];
        $params = [];

        $keyword = isset($filters['q']) ? trim((string) $filters['q']) : '';

        if ($keyword !== '') {
            $where[] = '(j.`title` LIKE ? OR j.`description` LIKE ? OR c.`company_name` LIKE ?)';
            // Escape the wildcard characters so a literal % is searched for.
            $like = '%' . $this->escapeLike($keyword) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['category'])) {
            $where[] = 'j.`category_id` = ?';
            $params[] = (int) $filters['category'];
        }

        if (!empty($filters['job_type']) && $this->isValidType($filters['job_type'])) {
            $where[] = 'j.`job_type` = ?';
            $params[] = $filters['job_type'];
        }

        $location = isset($filters['location']) ? trim((string) $filters['location']) : '';

        if ($location !== '') {
            $where[] = 'j.`location` LIKE ?';
            $params[] = '%' . $this->escapeLike($location) . '%';
        }

        // A job matches when either end of its range reaches the minimum.
        if (!empty($filters['salary_min'])) {
            $where[] = '(j.`salary_max` IS NULL OR j.`salary_max` >= ?)';
            $params[] = (int) $filters['salary_min'];
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*)
               FROM `jobs` j
               INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
              WHERE ' . $whereSql,
            $params
        );

        $perPage = max(1, (int) $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = isset($filters['page']) ? max(1, (int) $filters['page']) : 1;
        // A page past the end shows the last page rather than an empty grid.
        $page = min($page, $lastPage);

        $rows = Database::fetchAll(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . '
              WHERE ' . $whereSql . '
              ORDER BY ' . $this->sortClause($filters) . '
              LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /** Translate a sort key into a fixed ORDER BY fragment. */
    private function sortClause(array $filters)
    {
        $sort = isset($filters['sort']) ? $filters['sort'] : self::DEFAULT_SORT;

        if (!self::isValidSort($sort)) {
            $sort = self::DEFAULT_SORT;
        }

        switch ($sort) {
            case 'oldest':
                return 'j.`created_at` ASC, j.`id` ASC';
            case 'title':
                return 'j.`title` ASC, j.`id` ASC';
            case 'salary':
                // Listings with no salary go last rather than counting as zero.
                return '(j.`salary_max` IS NULL) ASC, j.`salary_max` DESC, j.`id` DESC';
            default:
                return 'j.`created_at` DESC, j.`id` DESC';
        }
    }

    /** Escape LIKE wildcards in user input. */
    private function escapeLike($value)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $value);
    }

    /**
     * Every listing on the platform, for the admin review queue.
     *
     * Uses the same joined columns as the public listing query so the admin
     * table can show the company and category without a second query.
     *
     * @param string|null $status Restrict to one status, or null for all
     * @param int         $page
     * @param int         $perPage
     *
     * @return array{rows: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateAll($status = null, $page = 1, $perPage = 20)
    {
        $where = '1 = 1';
        $params = [];

        if ($status !== null && $this->isValidStatus($status)) {
            $where .= ' AND j.`status` = ?';
            $params[] = $status;
        }

        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `jobs` j WHERE ' . $where,
            $params
        );

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $rows = Database::fetchAll(
            'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . '
              WHERE ' . $where . '
              ORDER BY j.`created_at` DESC, j.`id` DESC
              LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }
}
