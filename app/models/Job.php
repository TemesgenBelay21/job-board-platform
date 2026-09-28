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
    public function forCompany($companyId, $status = null)
    {
        $sql = 'SELECT ' . self::LISTING_COLUMNS . ' ' . self::LISTING_JOINS . ' WHERE j.`company_id` = ?';
        $params = [(int) $companyId];

        if ($status !== null) {
            $sql .= ' AND j.`status` = ?';
            $params[] = $status;
        }

        return Database::fetchAll(
            $sql . ' ORDER BY j.`created_at` DESC, j.`id` DESC',
            $params
        );
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

    /** Set the status of a listing, e.g. when reopening or closing it. */
    public function setStatus($jobId, $status)
    {
        if (!$this->isValidStatus($status)) {
            throw new \InvalidArgumentException('Unknown job status: ' . $status);
        }

        return $this->update($jobId, ['status' => $status]);
    }
}
