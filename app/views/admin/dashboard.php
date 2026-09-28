<?php
/**
 * The admin overview: platform totals, the listing review queue and new accounts.
 *
 * Expects: $userCounts, $jobCounts, $applicationTotal, $pendingReview,
 *          $pendingReviewTotal, $recentUsers
 */
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Overview</h1>
        <p class="page-head__subtitle">Platform activity at a glance.</p>
    </div>

    <?php if ($pendingReviewTotal > 0): ?>
        <a class="btn btn--primary" href="<?= url('admin/jobs', ['status' => 'pending']) ?>">
            Review <?= e($pendingReviewTotal) ?> pending listing<?= $pendingReviewTotal === 1 ? '' : 's' ?>
        </a>
    <?php endif; ?>
</div>

<div class="stat-row">
    <a class="stat-card" href="<?= url('admin/users', ['role' => 'seeker']) ?>">
        <span class="stat-card__value"><?= e($userCounts['seeker']) ?></span>
        <span class="stat-card__label">Seekers</span>
    </a>
    <a class="stat-card" href="<?= url('admin/users', ['role' => 'employer']) ?>">
        <span class="stat-card__value"><?= e($userCounts['employer']) ?></span>
        <span class="stat-card__label">Employers</span>
    </a>
    <a class="stat-card" href="<?= url('admin/jobs', ['status' => 'open']) ?>">
        <span class="stat-card__value"><?= e($jobCounts['open']) ?></span>
        <span class="stat-card__label">Live listings</span>
    </a>
    <a class="stat-card" href="<?= url('admin/jobs', ['status' => 'pending']) ?>">
        <span class="stat-card__value"><?= e($jobCounts['pending']) ?></span>
        <span class="stat-card__label">Awaiting review</span>
    </a>
</div>

<?php if ($userCounts['inactive'] > 0): ?>
    <p class="card__meta"><?= e($userCounts['inactive']) ?> deactivated account<?= $userCounts['inactive'] === 1 ? '' : 's' ?> on the platform.</p>
<?php endif; ?>

<div class="mt-4">
    <h2 class="card__title">Waiting for review</h2>

    <?php if (empty($pendingReview)): ?>
        <div class="card">
            <p class="card__meta">Nothing is waiting. Every listing has been looked at.</p>
        </div>
    <?php else: ?>
        <div class="card-list">
            <?php foreach ($pendingReview as $job): ?>
                <article class="card application-card">
                    <div class="application-card__main">
                        <h3 class="card__title"><?= e($job['title']) ?></h3>
                        <p class="card__meta">
                            <?= e($job['company_name']) ?> &middot;
                            <?= e($job['location']) ?> &middot;
                            <?= e(humanize($job['job_type'])) ?>
                        </p>
                        <p class="card__meta">Posted <?= e(time_ago($job['created_at'])) ?></p>
                    </div>

                    <div class="application-card__side">
                        <a class="btn btn--secondary btn--small" href="<?= url('jobs/' . (int) $job['id']) ?>">Read it</a>

                        <form method="post" action="<?= url('admin/jobs/' . (int) $job['id'] . '/status') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="open">
                            <input type="hidden" name="return_to" value="<?= e(url('admin/jobs', ['status' => 'pending'])) ?>">
                            <button class="btn btn--primary btn--small" type="submit">Approve</button>
                        </form>

                        <form method="post" action="<?= url('admin/jobs/' . (int) $job['id'] . '/status') ?>"
                              data-confirm="Close this listing? The employer will be told.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="closed">
                            <input type="hidden" name="return_to" value="<?= e(url('admin/jobs', ['status' => 'pending'])) ?>">
                            <button class="btn btn--danger btn--small" type="submit">Close</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="mt-3"><a href="<?= url('admin/jobs', ['status' => 'pending']) ?>">See the whole queue</a></p>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h2 class="card__title">Newest accounts</h2>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><strong><?= e($user['name']) ?></strong></td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <span class="badge badge--<?= e($user['role'] === 'admin' ? 'primary' : 'neutral') ?>">
                                <?= e(humanize($user['role'])) ?>
                            </span>
                            <?php if ($user['status'] !== 'active'): ?>
                                <span class="badge badge--muted">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= e(format_date($user['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
