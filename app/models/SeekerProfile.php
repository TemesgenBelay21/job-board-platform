<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Job seeker profile.
 *
 * One profile per seeker, created at registration time so applications and
 * saved jobs always have a profile to attach to.
 */
class SeekerProfile extends Model
{
    protected $table = 'seeker_profiles';

    protected $fillable = [
        'user_id',
        'phone',
        'location',
        'headline',
        'skills',
        'bio',
        'resume_path',
    ];

    public function findByUser($userId)
    {
        return Database::fetch(
            'SELECT * FROM `seeker_profiles` WHERE `user_id` = ? LIMIT 1',
            [(int) $userId]
        );
    }

    /**
     * A profile joined with the owner's account details, which every profile
     * page and application row needs.
     */
    public function findWithUser($userId)
    {
        return Database::fetch(
            'SELECT s.*,
                    u.`name`,
                    u.`email`,
                    u.`role`,
                    u.`status`
             FROM `seeker_profiles` s
             INNER JOIN `users` u ON u.`id` = s.`user_id`
             WHERE s.`user_id` = ?
             LIMIT 1',
            [(int) $userId]
        );
    }

    public function findWithUserById($seekerId)
    {
        return Database::fetch(
            'SELECT s.*,
                    u.`name`,
                    u.`email`,
                    u.`role`,
                    u.`status`
             FROM `seeker_profiles` s
             INNER JOIN `users` u ON u.`id` = s.`user_id`
             WHERE s.`id` = ?
             LIMIT 1',
            [(int) $seekerId]
        );
    }

    /** Create the empty profile that registration promises the user. */
    public function createForUser($userId)
    {
        return $this->create(['user_id' => (int) $userId]);
    }

    public function setResume($seekerId, $path)
    {
        return $this->update($seekerId, ['resume_path' => $path]);
    }

    /** Skills are stored as a comma separated string. */
    public function skills($profile)
    {
        if (empty($profile['skills'])) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $profile['skills']))));
    }

    /** How many applications this seeker has submitted. */
    public function applicationCount($seekerId)
    {
        // Counted on applications, not on this table, so the query cannot be
        // served by the base Model helpers bound to seeker_profiles.
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `applications` WHERE `seeker_id` = ?',
            [(int) $seekerId]
        );
    }
}
