<?php
/**
 * Home page: the latest open listings.
 *
 * Expects: $jobs, $user
 */
?>
<div class="section__header">
    <div>
        <h2>Latest openings</h2>
        <p>Freshly posted roles from companies on the platform.</p>
    </div>
    <a class="btn btn--secondary" href="<?= url('jobs') ?>">Browse all jobs</a>
</div>

<?php if (empty($jobs)): ?>
    <div class="empty-state">
        <h3>No open jobs yet</h3>
        <p>Check back soon, or post the first listing if you are hiring.</p>
    </div>
<?php else: ?>
    <div class="job-list">
        <?php foreach ($jobs as $job): ?>
            <?= \App\Core\View::partial('jobs/card', [
                'job' => $job,
                'user' => isset($user) ? $user : null,
                'showSaveButton' => false,
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
