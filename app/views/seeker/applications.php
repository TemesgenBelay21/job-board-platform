<?php
/**
 * A seeker's application history with a status filter.
 *
 * Expects: $applications, $statusCounts, $activeStatus, $statusLabels
 */
$active = isset($activeStatus) ? $activeStatus : '';
$labels = isset($statusLabels) ? $statusLabels : [];
$total = isset($statusCounts) ? array_sum($statusCounts) : 0;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">My applications</h1>
        <p class="page-head__subtitle">
            <?= e($total) ?> application<?= $total === 1 ? '' : 's' ?> sent.
        </p>
    </div>
    <a class="btn btn--primary" href="<?= url('jobs') ?>">Find more jobs</a>
</div>

<nav class="filter-pills" aria-label="Filter by status">
    <a class="filter-pill<?= $active === '' ? ' is-active' : '' ?>" href="<?= url('seeker/applications') ?>">
        All <span class="filter-pill__count"><?= e($total) ?></span>
    </a>
    <?php foreach ($labels as $key => $label): ?>
        <a class="filter-pill<?= $active === $key ? ' is-active' : '' ?>" href="<?= url('seeker/applications', ['status' => $key]) ?>">
            <?= e($label) ?> <span class="filter-pill__count"><?= e(isset($statusCounts[$key]) ? $statusCounts[$key] : 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($applications)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">Nothing here yet</h2>
        <p class="empty-state__text">
            <?php if ($active === ''): ?>
                You have not applied to any jobs yet.
            <?php else: ?>
                You have no applications with this status.
            <?php endif; ?>
        </p>
        <a class="btn btn--primary" href="<?= url('jobs') ?>">Browse jobs</a>
    </div>
<?php else: ?>
    <div class="card-list">
        <?php foreach ($applications as $application): ?>
            <?php $jobClosed = $application['job_status'] !== 'open'; ?>
            <article class="card application-card">
                <div class="application-card__main">
                    <h2 class="card__title">
                        <a href="<?= url('jobs/' . $application['job_id']) ?>"><?= e($application['title']) ?></a>
                    </h2>
                    <p class="card__meta">
                        <?= e($application['company_name']) ?> &middot;
                        <?= e($application['location']) ?> &middot;
                        <?= e(humanize($application['job_type'])) ?>
                    </p>
                    <p class="card__meta">Applied <?= e(format_date($application['applied_at'])) ?></p>
                </div>

                <div class="application-card__side">
                    <span class="badge badge--<?= e($application['status']) ?>">
                        <?= e(isset($labels[$application['status']]) ? $labels[$application['status']] : $application['status']) ?>
                    </span>
                    <?php if ($jobClosed): ?>
                        <span class="badge badge--muted">Listing closed</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
