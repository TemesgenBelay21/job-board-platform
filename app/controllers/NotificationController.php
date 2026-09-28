<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;

/**
 * The notification inbox shared by every role.
 *
 * Notifications are created by the rest of the app: an application notifies the
 * employer, a status change notifies the seeker, an admin approval notifies the
 * employer.
 */
class NotificationController extends Controller
{
    /**
     * The signed in user's notifications, newest first.
     */
    public function index()
    {
        $this->requireLogin();
        $userId = $this->userId();

        echo $this->view('notifications/index', [
            'title' => 'Notifications',
            'notifications' => (new Notification())->forUser($userId, 30),
            'unreadCount' => (new Notification())->unreadCount($userId),
        ]);
    }

    /**
     * Mark one notification read. POST only, so a link prefetch cannot change
     * anything.
     */
    public function markRead($id)
    {
        $this->requireMethod('POST');
        $this->requireLogin();
        $this->verifyCsrf();

        $userId = $this->userId();
        $notification = (new Notification())->findForUser((int) $id, $userId);

        if ($notification === null) {
            throw new \App\Core\HttpException(404, 'That notification could not be found.');
        }

        (new Notification())->markRead($notification['id'], $userId);

        clear_old_input();
        flash_success('Notification marked as read.');

        redirect_safe(input_string('return_to', ''), url('notifications'));
    }

    /**
     * Mark everything read.
     */
    public function markAllRead()
    {
        $this->requireMethod('POST');
        $this->requireLogin();
        $this->verifyCsrf();

        $userId = $this->userId();
        $count = (new Notification())->markAllRead($userId);

        clear_old_input();
        flash_success($count > 0
            ? $count . ' notification' . ($count === 1 ? '' : 's') . ' marked as read.'
            : 'You had no unread notifications.');

        redirect_safe(input_string('return_to', ''), url('notifications'));
    }
}
