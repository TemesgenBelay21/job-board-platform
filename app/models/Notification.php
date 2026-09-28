<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * In-app notifications shown in the header bell.
 *
 * Rows are created for the account they concern, so a seeker sees status
 * changes and an employer sees new applications.
 */
class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'message',
        'is_read',
    ];

    /**
     * Queue a notification for a user.
     *
     * @return int The new id
     */
    public function notify($userId, $message)
    {
        return $this->create([
            'user_id' => (int) $userId,
            'message' => mb_substr($message, 0, 255),
            'is_read' => 0,
        ]);
    }

    /** How many unread notifications a user has, for the bell badge. */
    public function unreadCount($userId)
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0',
            [(int) $userId]
        );
    }

    /** A user's notifications, newest first. */
    public function forUser($userId, $limit = 10)
    {
        return $this->findAll('`created_at` DESC, `id` DESC', $limit, '`user_id` = ?', [(int) $userId]);
    }

    public function findForUser($id, $userId)
    {
        return Database::fetch(
            'SELECT * FROM `notifications` WHERE `id` = ? AND `user_id` = ? LIMIT 1',
            [(int) $id, (int) $userId]
        );
    }

    /**
     * Mark one notification read, but only if it belongs to the user.
     *
     * @return bool True when a row was updated
     */
    public function markRead($id, $userId)
    {
        return $this->update(
            $this->whereIdAndUser($id, $userId),
            ['is_read' => 1]
        ) > 0;
    }

    /** Mark every unread notification for a user as read. */
    public function markAllRead($userId)
    {
        return Database::execute(
            'UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = ? AND `is_read` = 0',
            [(int) $userId]
        );
    }

    public function unread($userId, $limit = 5)
    {
        return $this->findAll(
            '`created_at` DESC, `id` DESC',
            $limit,
            '`user_id` = ? AND `is_read` = 0',
            [(int) $userId]
        );
    }

    /**
     * Resolve a (notification, user) pair to an id, or null when the
     * notification does not belong to that user.
     */
    private function whereIdAndUser($id, $userId)
    {
        $notification = $this->findForUser($id, $userId);

        return $notification === null ? null : (int) $notification['id'];
    }
}
