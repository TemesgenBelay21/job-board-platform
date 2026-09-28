<?php
/**
 * Home page hero: headline and the main job search bar.
 *
 * Expects: $stats
 */
?>
<section class="hero">
    <div class="container hero__inner">
        <span class="hero__eyebrow">
            <?= e(number_format($stats['open_jobs'])) ?> open positions right now
        </span>

        <h1>Find the job that actually fits</h1>

        <p class="hero__lead">
            Search openings from companies hiring now. Build a profile once, apply in
            a couple of clicks, and follow every application from submitted to hired.
        </p>

        <form class="hero__search" method="get" action="<?= url('jobs') ?>" role="search">
            <label class="visually-hidden" for="hero-q">Job title, keyword or company</label>
            <input
                class="input"
                type="search"
                id="hero-q"
                name="q"
                placeholder="Job title, skill or company"
                value="<?= e(query_param('q')) ?>"
                autocomplete="off"
            >
            <button class="btn btn--primary btn--lg" type="submit">Search jobs</button>
        </form>

        <div class="hero__stats">
            <div class="hero__stat">
                <strong><?= e(number_format($stats['open_jobs'])) ?></strong>
                <span>Open jobs</span>
            </div>
            <div class="hero__stat">
                <strong><?= e(number_format($stats['categories'])) ?></strong>
                <span>Categories</span>
            </div>
            <div class="hero__stat">
                <strong>Free</strong>
                <span>For job seekers</span>
            </div>
        </div>

        <?php if (empty($user)): ?>
            <div class="hero__actions">
                <a class="btn btn--accent btn--lg" href="<?= url('auth/register') ?>">Create a free profile</a>
                <a class="btn btn--secondary btn--lg" href="<?= url('auth/register?role=employer') ?>">I am hiring</a>
            </div>
        <?php endif; ?>
    </div>
</section>
