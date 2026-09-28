<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Forgot-password tokens.
 *
 * Only the SHA-256 hash of a token is stored, so a leaked database dump cannot
 * be turned into working reset links. A token is valid until it expires or
 * until it is used, whichever comes first.
 */
class PasswordReset extends Model
{
    protected $table = 'password_resets';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    /** Minutes a freshly issued reset link stays valid. */
    const LIFETIME_MINUTES = 60;

    /**
     * Issue a reset token for a user, replacing any link they already have.
     *
     * @return string The raw token, which exists only in this return value and
     *                in the message sent to the user
     */
    public function createForUser($userId)
    {
        $userId = (int) $userId;

        // Only one live link per account: the newest request wins.
        $this->invalidateForUser($userId);

        $token = self::generateToken();

        $this->create([
            'user_id' => $userId,
            'token_hash' => self::hashToken($token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + (self::LIFETIME_MINUTES * 60)),
        ]);

        return $token;
    }

    /**
     * Look up a live token.
     *
     * @param string $rawToken The token from the emailed link
     *
     * @return array|null The row, or null when it is unknown, spent or expired
     */
    public function findValidByToken($rawToken)
    {
        $rawToken = (string) $rawToken;

        if (!self::looksLikeToken($rawToken)) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM `password_resets`
              WHERE `token_hash` = ?
                AND `used_at` IS NULL
                AND `expires_at` > UTC_TIMESTAMP()
              LIMIT 1',
            [self::hashToken($rawToken)]
        );
    }

    /**
     * Spend a token so the same link cannot be used twice.
     *
     * @return bool True when this call is the one that redeemed it
     */
    public function consume($id)
    {
        return Database::execute(
            'UPDATE `password_resets` SET `used_at` = UTC_TIMESTAMP()
              WHERE `id` = ? AND `used_at` IS NULL',
            [(int) $id]
        ) > 0;
    }

    /** Mark every outstanding token for a user as spent. */
    public function invalidateForUser($userId)
    {
        return Database::execute(
            'UPDATE `password_resets` SET `used_at` = UTC_TIMESTAMP()
              WHERE `user_id` = ? AND `used_at` IS NULL',
            [(int) $userId]
        );
    }

    /** Remove tokens that can no longer be used, e.g. from a scheduled run. */
    public function deleteStale()
    {
        return Database::execute(
            'DELETE FROM `password_resets`
              WHERE `used_at` IS NOT NULL
                 OR `expires_at` < UTC_TIMESTAMP()'
        );
    }

    /** A cryptographically random token, as hex. */
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /** Tokens are 64 hex characters; anything else cannot be ours. */
    public static function looksLikeToken($token)
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    public static function hashToken($rawToken)
    {
        return hash('sha256', (string) $rawToken);
    }
}
