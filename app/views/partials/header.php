<?php
/**
 * Site header: brand, primary navigation and the account menu.
 *
 * Available: $user (array|null), $unreadCount (int)
 */
$user = isset($user) ? $user : null;
$unreadCount = isset($unreadCount) ? (int) $unreadCount : 0;
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= url('/') ?>">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"></rect>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </span>
            <span class="brand__name"><?= e(app_name()) ?></span>
        </a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation">
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>

        <nav class="primary-nav" id="primary-nav" data-nav>
            <ul class="primary-nav__list">
                <li><a href="<?= url('jobs') ?>" class="<?= active_class('jobs') ?>">Find jobs</a></li>
                </ul>

            <?php if ($user === null): ?>
                <ul class="primary-nav__list primary-nav__list--actions">
                    <li><a href="<?= url('auth/login') ?>" class="<?= active_class('auth/login') ?>">Log in</a></li>
                    <li><a class="btn btn--primary btn--sm" href="<?= url('auth/register') ?>">Create account</a></li>
                </ul>
            <?php else: ?>
                <ul class="primary-nav__list primary-nav__list--actions">
                    <li>
                        <a href="<?= url('notifications') ?>" class="nav-bell<?= $unreadCount > 0 ? ' nav-bell--unread' : '' ?>" aria-label="Notifications<?= $unreadCount > 0 ? ', ' . $unreadCount . ' unread' : '' ?>">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
                            </svg>
                            <?php if ($unreadCount > 0): ?>
                                <span class="nav-bell__count"><?= $unreadCount > 9 ? '9+' : e($unreadCount) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-user">
                        <button class="nav-user__toggle" type="button" data-user-menu-toggle aria-expanded="false">
                            <span class="nav-user__name"><?= e($user['name']) ?></span>
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </button>
                        <div class="nav-user__menu" data-user-menu hidden>
                            <p class="nav-user__role"><?= e(humanize($user['role'])) ?><?= $user['role'] === 'employer' ? '' : '' ?></p>
                            <ul>
                                <li><a href="<?= url('profile') ?>">My profile</a></li>
                                <?php if ($user['role'] === 'employer'): ?>
                                    <li><a href="<?= url('employer/dashboard') ?>">Dashboard</a></li>
                                    <li><a href="<?= url('employer/company') ?>">Company profile</a></li>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <li><a href="<?= url('admin/dashboard') ?>">Admin dashboard</a></li>
                                <?php else: ?>
                                    <li><a href="<?= url('seeker/applications') ?>">My applications</a></li>
                                    <li><a href="<?= url('seeker/saved-jobs') ?>">Saved jobs</a></li>
                                <?php endif; ?>
                                <li>
                                    <form method="post" action="<?= url('auth/logout') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="nav-user__logout">Sign out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            <?php endif; ?>
        </nav>
    </div>
</header>
