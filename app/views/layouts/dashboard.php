<?php
/**
 * Dashboard layout with a sidebar, used by employers and admins.
 *
 * Available: $content, $title, $user, $sidebar, $sidebarTitle
 */
$user = isset($user) ? $user : null;
$pageTitle = isset($title) && $title !== '' ? $title . ' · ' . app_name() : app_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?= \App\Core\View::stack('head') ?>
</head>
<body class="page page--dashboard">
    <aside class="sidebar" data-sidebar>
        <a class="brand sidebar__brand" href="<?= url('/') ?>">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"></rect>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </span>
            <span class="brand__name"><?= e(app_name()) ?></span>
        </a>

        <?php if (!empty($sidebarTitle)): ?>
            <p class="sidebar__title"><?= e($sidebarTitle) ?></p>
        <?php endif; ?>

        <?= \App\Core\View::partial('sidebar', ['user' => $user]) ?>
    </aside>

    <div class="dashboard">
        <header class="dashboard__topbar">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="sidebar" aria-label="Toggle menu">
                <span class="nav-toggle__bar"></span>
                <span class="nav-toggle__bar"></span>
                <span class="nav-toggle__bar"></span>
            </button>

            <div class="dashboard__topbar-actions">
                <a href="<?= url('notifications') ?>" class="nav-bell<?= !empty($unreadCount) && $unreadCount > 0 ? ' nav-bell--unread' : '' ?>" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
                    </svg>
                    <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                        <span class="nav-bell__count"><?= $unreadCount > 9 ? '9+' : e($unreadCount) ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($user !== null): ?>
                    <div class="nav-user nav-user--inline">
                        <button class="nav-user__toggle" type="button" data-user-menu-toggle aria-expanded="false">
                            <span class="nav-user__name"><?= e($user['name']) ?></span>
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </button>
                        <div class="nav-user__menu" data-user-menu hidden>
                            <p class="nav-user__role"><?= e(humanize($user['role'])) ?></p>
                            <ul>
                                <li><a href="<?= url('profile') ?>">My profile</a></li>
                                <li>
                                    <form method="post" action="<?= url('auth/logout') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="nav-user__logout">Sign out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="dashboard__main" id="main">
            <div class="container container--dashboard">
                <?= render_flashes() ?>
                <?= $content ?>
            </div>
        </main>
    </div>

    <div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?= \App\Core\View::stack('scripts') ?>
</body>
</html>
