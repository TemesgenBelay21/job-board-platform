<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Employer company profile.
 *
 * Created at registration so an employer can post a job immediately, and
 * edited later from the employer dashboard.
 */
class CompanyProfile extends Model
{
    protected $table = 'company_profiles';

    protected $fillable = [
        'user_id',
        'company_name',
        'description',
        'website',
        'location',
        'logo',
    ];

    public function findByUser($userId)
    {
        return Database::fetch(
            'SELECT * FROM `company_profiles` WHERE `user_id` = ? LIMIT 1',
            [(int) $userId]
        );
    }

    public function findWithUser($userId)
    {
        return Database::fetch(
            'SELECT c.*,
                    u.`name` AS `owner_name`,
                    u.`email` AS `owner_email`
             FROM `company_profiles` c
             INNER JOIN `users` u ON u.`id` = c.`user_id`
             WHERE c.`user_id` = ?
             LIMIT 1',
            [(int) $userId]
        );
    }

    public function createForUser($userId, $companyName = null)
    {
        return $this->create([
            'user_id' => (int) $userId,
            'company_name' => $companyName !== null ? $companyName : 'My company',
        ]);
    }

    public function setLogo($companyId, $logo)
    {
        return $this->update($companyId, ['logo' => $logo]);
    }

    /**
     * A company as the public sees it, used by the company page and its
     * listing count.
     */
    public function findPublic($companyId)
    {
        return Database::fetch(
            'SELECT * FROM `company_profiles` WHERE `id` = ? LIMIT 1',
            [(int) $companyId]
        );
    }

    /** The first letter of the company name, for the placeholder avatar. */
    public static function initial($companyName)
    {
        return mb_strtoupper(mb_substr((string) $companyName, 0, 1));
    }

    /** True when the value already looks like a usable http(s) URL. */
    public static function isValidWebsite($website)
    {
        if ($website === '' || $website === null) {
            return true;
        }

        return (bool) filter_var($website, FILTER_VALIDATE_URL)
            && preg_match('#^https?://#i', $website) === 1;
    }

    /** Add a scheme to a bare domain so the link is clickable. */
    public static function normalizeWebsite($website)
    {
        $website = trim((string) $website);

        if ($website === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        return $website;
    }
}
