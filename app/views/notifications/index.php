<?php
/**
 * The notification inbox. Expects: $notifications, $unreadCount
 */
$currentUser = isset($user) ? $user : null;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Notifications</h1>
        <p class="page-head__subtitle">
            <?php if ($unreadCount > 0): ?>
                <?= e($unreadCount) ?> unread of <?= e(count($notifications)) ?> shown.
            <?php else: ?>
                You are all caught up.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($unreadCount > 0): ?>
        <form method="post" action="<?= url('notifications/read-all') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="<?= e(url('notifications')) ?>">
            <button class="btn btn--secondary" type="submit">Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">Nothing here yet</h2>
        <p class="empty-state__text">
            Activity on your account will show up here, such as an application update.
        </p>
        <a class="btn btn--primary" href="<?= url('jobs') ?>">Browse jobs</a>
    </div>
<?php else: ?>
    <ul class="notification-list">
        <?php foreach ($notifications as $notification): ?>
            <li class="notification-item<?= $notification['is_read'] ? ' notification-item--read' : ' notification-item--unread' ?>">
                <span class="notification-item__dot" aria-hidden="true"></span>

                <div class="notification-item__body">
                    <p><?= e($notification['message']) ?></p>
                    <p class="notification-item__time"><?= e(time_ago($notification['created_at'])) ?></p>
                </div>

                <?php if (!$notification['is_read']): ?>
                    <form method="post" action="<?= url('notifications/' . (int) $notification['id'] . '/read') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= e(url('notifications')) ?>">
                        <button class="btn btn--ghost btn--sm" type="submit">Mark read</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
