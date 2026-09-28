<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Job categories.
 *
 * A category is referenced by jobs, so deleting one that is still in use would
 * either fail or strip the category from live listings. The delete method
 * therefore reports what would happen and the admin view asks for confirmation.
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['name'];

    /** All categories alphabetically, with a live listing count for each. */
    public function allWithCounts()
    {
        return Database::fetchAll(
            "SELECT cat.`id`,
                    cat.`name`,
                    cat.`created_at`,
                    COUNT(j.`id`) AS job_count,
                    SUM(CASE WHEN j.`status` = 'open' THEN 1 ELSE 0 END) AS open_job_count
             FROM `categories` cat
             LEFT JOIN `jobs` j ON j.`category_id` = cat.`id`
             GROUP BY cat.`id`, cat.`name`, cat.`created_at`
             ORDER BY cat.`name` ASC"
        );
    }

    /** How many jobs use a category. */
    public function jobCount($categoryId)
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `jobs` WHERE `category_id` = ?',
            [(int) $categoryId]
        );
    }

    public function findByName($name)
    {
        return Database::fetch(
            'SELECT * FROM `categories` WHERE `name` = ? LIMIT 1',
            [trim($name)]
        );
    }

    public function nameExists($name, $exceptId = null)
    {
        $sql = 'SELECT COUNT(*) FROM `categories` WHERE `name` = ?';
        $params = [trim($name)];

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = (int) $exceptId;
        }

        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    /**
     * Remove a category, but only when no job still points at it.
     *
     * @return array{deleted: bool, jobs: int}
     */
    public function deleteIfUnused($categoryId)
    {
        $jobs = $this->jobCount($categoryId);

        if ($jobs > 0) {
            return ['deleted' => false, 'jobs' => $jobs];
        }

        $this->delete($categoryId);

        return ['deleted' => true, 'jobs' => 0];
    }
}
