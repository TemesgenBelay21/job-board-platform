<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * A seeker's bookmarked jobs.
 *
 * The unique key on (seeker_id, job_id) is the duplicate guard, so saving the
 * same job twice is a no-op rather than a second row.
 */
class SavedJob extends Model
{
    protected $table = 'saved_jobs';

    protected $fillable = [
        'seeker_id',
        'job_id',
    ];

    public function findForSeekerJob($seekerId, $jobId)
    {
        return Database::fetch(
            'SELECT * FROM `saved_jobs` WHERE `seeker_id` = ? AND `job_id` = ? LIMIT 1',
            [(int) $seekerId, (int) $jobId]
        );
    }

    /**
     * Save a job for a seeker.
     *
     * @return bool True when a new row was created
     */
    public function save($seekerId, $jobId)
    {
        if ($this->findForSeekerJob($seekerId, $jobId) !== null) {
            return false;
        }

        try {
            $this->create([
                'seeker_id' => (int) $seekerId,
                'job_id' => (int) $jobId,
            ]);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return false;
            }

            throw $exception;
        }

        return true;
    }

    /**
     * Remove a saved job, scoped to the seeker so nobody can delete another's.
     *
     * @return bool True when a row was removed
     */
    public function remove($seekerId, $jobId)
    {
        return Database::execute(
            'DELETE FROM `saved_jobs` WHERE `seeker_id` = ? AND `job_id` = ?',
            [(int) $seekerId, (int) $jobId]
        ) > 0;
    }

    /**
     * Saved jobs with the listing details, newest first.
     *
     * @return array
     */
    public function forSeeker($seekerId, $limit = 0, $offset = 0)
    {
        $sql = 'SELECT sj.`id` AS `saved_id`,
                       sj.`created_at` AS `saved_at`,
                       j.*
                  FROM `saved_jobs` sj
                  INNER JOIN `jobs` j ON j.`id` = sj.`job_id`
                 WHERE sj.`seeker_id` = ?
                 ORDER BY sj.`created_at` DESC, sj.`id` DESC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return Database::fetchAll($sql, [(int) $seekerId]);
    }

    public function countForSeeker($seekerId)
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `saved_jobs` WHERE `seeker_id` = ?',
            [(int) $seekerId]
        );
    }

    /**
     * The ids of every job a seeker has saved, for marking the heart on cards.
     *
     * @return int[]
     */
    public function jobIdsForSeeker($seekerId)
    {
        $rows = Database::fetchAll(
            'SELECT `job_id` FROM `saved_jobs` WHERE `seeker_id` = ?',
            [(int) $seekerId]
        );

        return array_map('intval', array_column($rows, 'job_id'));
    }
}
