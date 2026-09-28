<?php
/**
 * A seeker's bookmarked jobs, reusing the shared job card.
 *
 * Expects: $jobs, $savedCount
 */
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Saved jobs</h1>
        <p class="page-head__subtitle">
            <?= e($savedCount) ?> job<?= $savedCount === 1 ? '' : 's' ?> bookmarked.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= url('jobs') ?>">Browse jobs</a>
</div>

<?php if (empty($jobs)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No saved jobs yet</h2>
        <p class="empty-state__text">Save a job while you are browsing and it will wait for you here.</p>
        <a class="btn btn--primary" href="<?= url('jobs') ?>">Find jobs</a>
    </div>
<?php else: ?>
    <div class="job-grid">
        <?php foreach ($jobs as $job): ?>
            <?php
            // The saved join selects sj.* and j.*, so the extra columns are
            // dropped to match what the card expects.
            $card = $job;
            unset($card['saved_id'], $card['saved_at']);
            $saved = true;
            $showSaveButton = true;
            $applied = false;
            ?>
            <?= \App\Core\View::partial('jobs/card', [
                'job' => $card,
                'saved' => $saved,
                'showSaveButton' => $showSaveButton,
                'applied' => $applied,
                'returnTo' => url('seeker/saved-jobs'),
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
