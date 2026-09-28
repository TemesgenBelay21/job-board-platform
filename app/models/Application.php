<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * A seeker's application to a job.
 *
 * The unique key on (seeker_id, job_id) is the real duplicate guard; hasApplied()
 * exists only so the UI can be helpful, because two clicks can still race.
 */
class Application extends Model
{
    protected $table = 'applications';

    protected $fillable = [
        'job_id',
        'seeker_id',
        'cover_letter',
        'resume_path',
        'status',
    ];

    /** The statuses an employer can move an application to, in order. */
    const STATUSES = ['submitted', 'under_review', 'shortlisted', 'rejected', 'hired'];

    /** Human readable labels for the status filter and the timeline. */
    const STATUS_LABELS = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Not selected',
        'hired' => 'Hired',
    ];

    /** Statuses that mean the application is still live. */
    const ACTIVE_STATUSES = ['submitted', 'under_review', 'shortlisted', 'hired'];

    public function hasApplied($seekerId, $jobId)
    {
        return $this->exists('`seeker_id` = ? AND `job_id` = ?', [(int) $seekerId, (int) $jobId]);
    }

    public function findForSeekerJob($seekerId, $jobId)
    {
        return Database::fetch(
            'SELECT * FROM `applications` WHERE `seeker_id` = ? AND `job_id` = ? LIMIT 1',
            [(int) $seekerId, (int) $jobId]
        );
    }

    /**
     * Create an application, refusing a second one for the same job.
     *
     * @return int|false The new id, or false when the seeker already applied
     */
    public function apply($seekerId, $jobId, $coverLetter, $resumePath = null)
    {
        if ($this->hasApplied($seekerId, $jobId)) {
            return false;
        }

        $values = [
            'seeker_id' => (int) $seekerId,
            'job_id' => (int) $jobId,
            'cover_letter' => $coverLetter === '' ? null : $coverLetter,
            'resume_path' => $resumePath,
        ];

        try {
            return $this->create($values);
        } catch (\PDOException $exception) {
            // 23000 is an integrity constraint violation: the unique key caught
            // a second insert for the same job, so the loser of the race stops here.
            if ($exception->getCode() === '23000') {
                return false;
            }

            throw $exception;
        }
    }

    /** A seeker's applications with the job and company they point at. */
    public function forSeeker($seekerId, $status = null)
    {
        $sql = 'SELECT a.*,
                       j.`title`,
                       j.`location`,
                       j.`job_type`,
                       j.`status` AS `job_status`,
                       j.`id` AS `job_id`,
                       c.`company_name`
                  FROM `applications` a
                  INNER JOIN `jobs` j ON j.`id` = a.`job_id`
                  INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
                 WHERE a.`seeker_id` = ?';

        $params = [(int) $seekerId];

        if ($status !== null && isset(self::STATUS_LABELS[$status])) {
            $sql .= ' AND a.`status` = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY a.`applied_at` DESC, a.`id` DESC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * How many applications a seeker has in each status, for the summary cards.
     *
     * @return array<string, int>
     */
    public function statusCounts($seekerId)
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        $rows = Database::fetchAll(
            'SELECT `status`, COUNT(*) AS `total` FROM `applications` WHERE `seeker_id` = ? GROUP BY `status`',
            [(int) $seekerId]
        );

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * One application with its job, company and seeker, for the employer view.
     *
     * @return array|null
     */
    public function findDetailed($id)
    {
        return Database::fetch(
            'SELECT a.*,
                    j.`title`,
                    j.`location`,
                    j.`job_type`,
                    j.`company_id`,
                    j.`status` AS `job_status`,
                    c.`company_name`,
                    s.`user_id`,
                    u.`name` AS `seeker_name`,
                    u.`email` AS `seeker_email`,
                    s.`phone` AS `seeker_phone`,
                    s.`location` AS `seeker_location`,
                    s.`headline` AS `seeker_headline`,
                    s.`skills` AS `seeker_skills`,
                    s.`resume_path` AS `profile_resume_path`
               FROM `applications` a
               INNER JOIN `jobs` j ON j.`id` = a.`job_id`
               INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
               INNER JOIN `seeker_profiles` s ON s.`id` = a.`seeker_id`
               INNER JOIN `users` u ON u.`id` = s.`user_id`
              WHERE a.`id` = ?
              LIMIT 1',
            [(int) $id]
        );
    }

    /**
     * Applications against one employer's jobs.
     *
     * @param int    $companyId
     * @param int    $jobId     Restrict to a single job, or 0 for all
     * @param string $status    Restrict to a status, or '' for all
     * @param int    $page
     * @param int    $perPage
     */
    public function paginateForCompany($companyId, $jobId = 0, $status = '', $page = 1, $perPage = 15)
    {
        $where = 'j.`company_id` = ?';
        $params = [(int) $companyId];

        if ($jobId > 0) {
            $where .= ' AND j.`id` = ?';
            $params[] = (int) $jobId;
        }

        if ($status !== '' && isset(self::STATUS_LABELS[$status])) {
            $where .= ' AND a.`status` = ?';
            $params[] = $status;
        }

        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `applications` a
               INNER JOIN `jobs` j ON j.`id` = a.`job_id`
              WHERE ' . $where,
            $params
        );

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $rows = Database::fetchAll(
            'SELECT a.*,
                    j.`title`,
                    j.`job_type`,
                    c.`company_name`,
                    u.`name` AS `seeker_name`,
                    u.`email` AS `seeker_email`,
                    s.`id` AS `seeker_id`
               FROM `applications` a
               INNER JOIN `jobs` j ON j.`id` = a.`job_id`
               INNER JOIN `company_profiles` c ON c.`id` = j.`company_id`
               INNER JOIN `seeker_profiles` s ON s.`id` = a.`seeker_id`
               INNER JOIN `users` u ON u.`id` = s.`user_id`
              WHERE ' . $where . '
              ORDER BY a.`applied_at` DESC, a.`id` DESC
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

    /**
     * Change an application's status, but only if it belongs to the employer.
     *
     * The company check is in the WHERE clause rather than a separate lookup,
     * so an employer cannot move an application that is not theirs even by
     * guessing its id.
     *
     * @return bool True when a row was updated
     */
    public function setStatusForCompany($applicationId, $companyId, $status)
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            return false;
        }

        return Database::execute(
            'UPDATE `applications` a
               INNER JOIN `jobs` j ON j.`id` = a.`job_id`
               SET a.`status` = ?
             WHERE a.`id` = ? AND j.`company_id` = ?',
            [$status, (int) $applicationId, (int) $companyId]
        ) > 0;
    }

    /** Per job applicant totals for an employer dashboard. */
    public function countsByStatusForCompany($companyId)
    {
        $rows = Database::fetchAll(
            'SELECT a.`status`, COUNT(*) AS `total`
               FROM `applications` a
               INNER JOIN `jobs` j ON j.`id` = a.`job_id`
              WHERE j.`company_id` = ?
              GROUP BY a.`status`',
            [(int) $companyId]
        );

        $counts = array_fill_keys(self::STATUSES, 0);

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
