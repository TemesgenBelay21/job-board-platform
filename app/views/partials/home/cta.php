<?php
/**
 * Home page: closing call to action.
 */
?>
<div class="cta-band">
    <div>
        <h2>Are you hiring?</h2>
        <p>Post a listing, review applicants and update their status from one dashboard.</p>
    </div>
    <div class="btn-row">
        <?php if (empty($user)): ?>
            <a class="btn btn--accent btn--lg" href="<?= url('auth/register?role=employer') ?>">Post a job</a>
        <?php elseif ($user['role'] === 'employer'): ?>
            <a class="btn btn--accent btn--lg" href="<?= url('employer/jobs/create') ?>">Post a job</a>
        <?php else: ?>
            <a class="btn btn--accent btn--lg" href="<?= url('jobs') ?>">Browse jobs</a>
        <?php endif; ?>
    </div>
</div>
