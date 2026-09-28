<?php
/**
 * The employer's own job listings, with a status filter.
 *
 * Expects: $company, $jobs, $counts, $activeStatus, $statusLabels
 */
$active = isset($activeStatus) ? $activeStatus : '';
$labels = isset($statusLabels) ? $statusLabels : [];
$total = isset($counts) ? array_sum($counts) : 0;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">My job listings</h1>
        <p class="page-head__subtitle"><?= e($total) ?> listing<?= $total === 1 ? '' : 's' ?> for <?= e($company['company_name']) ?>.</p>
    </div>
    <a class="btn btn--primary" href="<?= url('employer/jobs/create') ?>">Post a job</a>
</div>

<nav class="filter-pills" aria-label="Filter by status">
    <a class="filter-pill<?= $active === '' ? ' is-active' : '' ?>" href="<?= url('employer/jobs') ?>">
        All <span class="filter-pill__count"><?= e($total) ?></span>
    </a>
    <?php foreach ($labels as $key => $label): ?>
        <a class="filter-pill<?= $active === $key ? ' is-active' : '' ?>" href="<?= url('employer/jobs', ['status' => $key]) ?>">
            <?= e($label) ?> <span class="filter-pill__count"><?= e(isset($counts[$key]) ? $counts[$key] : 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($jobs)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No listings here</h2>
        <p class="empty-state__text">
            <?php if ($active === ''): ?>
                You have not posted a job yet.
            <?php else: ?>
                You have no listings with this status.
            <?php endif; ?>
        </p>
        <a class="btn btn--primary" href="<?= url('employer/jobs/create') ?>">Post a job</a>
    </div>
<?php else: ?>
    <div class="card-list">
        <?php foreach ($jobs as $job): ?>
            <?php
            $salary = salary_range($job['salary_min'], $job['salary_max']);
            $expired = $job['deadline'] !== null && strtotime($job['deadline']) < strtotime('today');
            $returnTo = url('employer/jobs', $active === '' ? [] : ['status' => $active]);
            ?>
            <article class="card application-card">
                <div class="application-card__main">
                    <h2 class="card__title">
                        <?php if ($job['status'] === 'open'): ?>
                            <a href="<?= url('jobs/' . (int) $job['id']) ?>"><?= e($job['title']) ?></a>
                        <?php else: ?>
                            <?= e($job['title']) ?>
                        <?php endif; ?>
                    </h2>
                    <p class="card__meta">
                        <?= e($job['location']) ?> &middot;
                        <?= e(humanize($job['job_type'])) ?>
                        <?php if ($salary !== ''): ?>
                            &middot; <?= e($salary) ?>
                        <?php endif; ?>
                    </p>
                    <p class="card__meta">
                        <?= e(!empty($job['category_name']) ? $job['category_name'] : 'No category') ?>
                        &middot; Posted <?= e(format_date($job['created_at'])) ?>
                        <?php if ($expired): ?>
                            &middot; <span class="badge badge--warning">Deadline passed</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="application-card__side">
                    <span class="badge badge--<?= e($job['status']) ?>"><?= e(humanize($job['status'])) ?></span>

                    <a class="btn btn--ghost btn--small" href="<?= url('employer/applicants', ['job' => (int) $job['id']]) ?>">
                        Applicants
                    </a>

                    <a class="btn btn--secondary btn--small" href="<?= url('employer/jobs/' . (int) $job['id'] . '/edit') ?>">Edit</a>

                    <?php if ($job['status'] === 'open'): ?>
                        <form method="post" action="<?= url('employer/jobs/' . (int) $job['id'] . '/close') ?>" data-confirm="Close this listing? It will be hidden from the job search.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                            <button class="btn btn--ghost btn--small" type="submit">Close</button>
                        </form>
                    <?php elseif ($job['status'] === 'closed'): ?>
                        <form method="post" action="<?= url('employer/jobs/' . (int) $job['id'] . '/reopen') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                            <button class="btn btn--ghost btn--small" type="submit">
                                <?= $job['approved_at'] !== null ? 'Reopen' : 'Send for approval' ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="<?= url('employer/jobs/' . (int) $job['id'] . '/delete') ?>"
                          data-confirm="Delete this listing for good? Any applications for it are deleted too.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <button class="btn btn--danger btn--small" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
