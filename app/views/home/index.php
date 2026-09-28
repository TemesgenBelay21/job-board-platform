<?php
/**
 * Home page.
 *
 * Composed of independent sections so each can be changed on its own.
 * Expects: $jobs, $categories, $stats
 */
?>
<?= \App\Core\View::partial('home/hero', ['stats' => $stats, 'user' => isset($user) ? $user : null]) ?>

<section class="section">
    <div class="container">
        <?= \App\Core\View::partial('home/latest-jobs', [
            'jobs' => $jobs,
            'user' => isset($user) ? $user : null,
        ]) ?>
    </div>
</section>

<?php if (!empty($categories)): ?>
    <section class="section">
        <div class="container">
            <?= \App\Core\View::partial('home/categories', ['categories' => $categories]) ?>
        </div>
    </section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <?= \App\Core\View::partial('home/how-it-works') ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <?= \App\Core\View::partial('home/cta') ?>
    </div>
</section>
