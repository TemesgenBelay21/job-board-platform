<?php
/**
 * Site footer.
 */
?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__about">
            <a class="brand brand--footer" href="<?= url('/') ?>">
                <span class="brand__name"><?= e(app_name()) ?></span>
            </a>
            <p>
                A job board where companies post openings and job seekers search,
                apply and track every application in one place.
            </p>
        </div>

        <nav class="site-footer__links" aria-label="Footer">
            <div>
                <h2>For job seekers</h2>
                <ul>
                    <li><a href="<?= url('jobs') ?>">Browse jobs</a></li>
                    <li><a href="<?= url('auth/register') ?>">Create a profile</a></li>
                    <li><a href="<?= url('seeker/saved-jobs') ?>">Saved jobs</a></li>
                </ul>
            </div>
            <div>
                <h2>For employers</h2>
                <ul>
                    <li><a href="<?= url('auth/register') ?>">Post a job</a></li>
                    <li><a href="<?= url('employer/dashboard') ?>">Employer dashboard</a></li>
                </ul>
            </div>
            <div>
                <h2>Account</h2>
                <ul>
                    <li><a href="<?= url('auth/login') ?>">Log in</a></li>
                    <li><a href="<?= url('auth/forgot-password') ?>">Forgot password</a></li>
                </ul>
            </div>
        </nav>
    </div>

    <div class="container site-footer__bottom">
        <p>&copy; <?= date('Y') ?> <?= e(app_name()) ?>. Built with PHP and MySQL.</p>
    </div>
</footer>
