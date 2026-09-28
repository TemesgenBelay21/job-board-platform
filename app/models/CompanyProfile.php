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
     * Update the editable parts of a company profile.
     *
     * `user_id` is not settable here: the profile is reached by the signed in
     * employer's own user id, and allowing it to be written would let a form
     * field move the profile onto another account.
     *
     * @param int   $companyId
     * @param array $values
     *
     * @return bool True when a row was updated
     */
    public function updateDetails($companyId, array $values)
    {
        unset($values['user_id'], $values['id'], $values['logo']);

        if ($values === []) {
            return false;
        }

        $set = implode(', ', array_map(function ($column) {
            return '`' . $column . '` = ?';
        }, array_keys($values)));

        $params = array_values($values);
        $params[] = (int) $companyId;

        // Note: this is the affected row count, not proof that a row matched.
        // MySQL reports 0 when the new values are identical to the old ones, so
        // a caller cannot use this to tell "not found" from "nothing to
        // change". Callers that care about ownership have to check the record
        // first.
        return Database::execute(
            'UPDATE `company_profiles` SET ' . $set . ' WHERE `id` = ?',
            $params
        ) > 0;
    }

    /** Clear the stored logo, leaving the column null. */
    public function clearLogo($companyId)
    {
        return Database::execute(
            'UPDATE `company_profiles` SET `logo` = NULL WHERE `id` = ?',
            [(int) $companyId]
        ) > 0;
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
