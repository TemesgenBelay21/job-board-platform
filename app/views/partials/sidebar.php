<?php
/**
 * Dashboard sidebar navigation, links depend on the signed in role.
 *
 * Available: $user (array|null), $unreadCount
 */
$user = isset($user) ? $user : null;
$role = $user !== null ? $user['role'] : '';
?>
<?php if ($role === 'employer'): ?>
    <nav class="sidebar__nav" aria-label="Employer">
        <ul>
            <li><a href="<?= url('employer/dashboard') ?>" class="<?= active_class('employer/dashboard') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>
                </span>
                Dashboard
            </a></li>
            <li><a href="<?= url('employer/jobs') ?>" class="<?= active_class('employer/jobs') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </span>
                My job listings
            </a></li>
            <li><a href="<?= url('employer/jobs/create') ?>" class="<?= active_class('employer/jobs/create') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg>
                </span>
                Post a job
            </a></li>
            <li><a href="<?= url('employer/company') ?>" class="<?= active_class('employer/company') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14"></path><path d="M9 9h.01M9 13h.01M15 9h.01M15 13h.01"></path></svg>
                </span>
                Company profile
            </a></li>
            <li><a href="<?= url('jobs') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
                </span>
                Browse jobs
            </a></li>
        </ul>
    </nav>
<?php elseif ($role === 'admin'): ?>
    <nav class="sidebar__nav" aria-label="Admin">
        <ul>
            <li><a href="<?= url('admin/dashboard') ?>" class="<?= active_class('admin/dashboard') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>
                </span>
                Overview
            </a></li>
            <li><a href="<?= url('admin/users') ?>" class="<?= active_class('admin/users') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.9"></path></svg>
                </span>
                Users
            </a></li>
            <li><a href="<?= url('admin/jobs') ?>" class="<?= active_class('admin/jobs') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </span>
                Job listings
            </a></li>
            <li><a href="<?= url('admin/categories') ?>" class="<?= active_class('admin/categories') ?>">
                <span class="sidebar__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h18"></path></svg>
                </span>
                Categories
            </a></li>
        </ul>
    </nav>
<?php else: ?>
    <nav class="sidebar__nav" aria-label="Account">
        <ul>
            <li><a href="<?= url('seeker/applications') ?>" class="<?= active_class('seeker/applications') ?>">My applications</a></li>
            <li><a href="<?= url('seeker/saved-jobs') ?>" class="<?= active_class('seeker/saved-jobs') ?>">Saved jobs</a></li>
            <li><a href="<?= url('profile') ?>" class="<?= active_class('profile') ?>">My profile</a></li>
        </ul>
    </nav>
<?php endif; ?>

<div class="sidebar__footer">
    <a href="<?= url('/') ?>">&larr; Back to site</a>
</div>
