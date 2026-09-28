<?php
/**
 * Every listing on the platform, with the review actions.
 *
 * Expects: $results, $counts, $activeStatus
 */
$rows = $results['rows'];
$baseQuery = $activeStatus !== '' ? ['status' => $activeStatus] : [];
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Job listings</h1>
        <p class="page-head__subtitle">
            <?= e($counts['pending']) ?> listing<?= $counts['pending'] === 1 ? '' : 's' ?> waiting for review.
        </p>
    </div>
</div>

<nav class="filter-pills" aria-label="Filter by status">
    <a class="filter-pill<?= $activeStatus === '' ? ' is-active' : '' ?>" href="<?= url('admin/jobs') ?>">
        All <span class="filter-pill__count"><?= e($counts['all']) ?></span>
    </a>
    <a class="filter-pill<?= $activeStatus === 'pending' ? ' is-active' : '' ?>" href="<?= url('admin/jobs', ['status' => 'pending']) ?>">
        Pending <span class="filter-pill__count"><?= e($counts['pending']) ?></span>
    </a>
    <a class="filter-pill<?= $activeStatus === 'open' ? ' is-active' : '' ?>" href="<?= url('admin/jobs', ['status' => 'open']) ?>">
        Live <span class="filter-pill__count"><?= e($counts['open']) ?></span>
    </a>
    <a class="filter-pill<?= $activeStatus === 'closed' ? ' is-active' : '' ?>" href="<?= url('admin/jobs', ['status' => 'closed']) ?>">
        Closed <span class="filter-pill__count"><?= e($counts['closed']) ?></span>
    </a>
</nav>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No listings here</h2>
        <p class="empty-state__text">Nothing matches this status.</p>
        <a class="btn btn--primary" href="<?= url('admin/jobs') ?>">Show all</a>
    </div>
<?php else: ?>
    <div class="card-list">
        <?php foreach ($rows as $job): ?>
            <?php
            $id = (int) $job['id'];
            $salary = salary_range($job['salary_min'], $job['salary_max']);
            $returnTo = url('admin/jobs', $baseQuery);
            ?>
            <article class="card application-card">
                <div class="application-card__main">
                    <h2 class="card__title">
                        <a href="<?= url('jobs/' . $id) ?>"><?= e($job['title']) ?></a>
                    </h2>
                    <p class="card__meta">
                        <?= e($job['company_name']) ?> &middot;
                        <?= e($job['location']) ?> &middot;
                        <?= e(humanize($job['job_type'])) ?>
                    </p>
                    <p class="card__meta">
                        <?= e(!empty($job['category_name']) ? $job['category_name'] : 'No category') ?>
                        <?php if ($salary !== ''): ?>
                            &middot; <?= e($salary) ?>
                        <?php endif; ?>
                        &middot; Posted <?= e(format_date($job['created_at'])) ?>
                        <?php if (!empty($job['approved_at'])): ?>
                            &middot; Approved <?= e(format_date($job['approved_at'])) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="application-card__side">
                    <span class="badge badge--<?= e($job['status']) ?>"><?= e(humanize($job['status'])) ?></span>

                    <?php if ($job['status'] !== 'open'): ?>
                        <form method="post" action="<?= url('admin/jobs/' . $id . '/status') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="open">
                            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                            <button class="btn btn--primary btn--small" type="submit">
                                <?= $job['status'] === 'pending' ? 'Approve' : 'Reopen' ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($job['status'] === 'open'): ?>
                        <form method="post" action="<?= url('admin/jobs/' . $id . '/status') ?>"
                              data-confirm="Close this listing? The employer will be told.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="closed">
                            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                            <button class="btn btn--danger btn--small" type="submit">Close</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($results['last_page'] > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($results['page'] > 1): ?>
                <a class="pagination__link" href="<?= url('admin/jobs', array_merge($baseQuery, ['page' => $results['page'] - 1])) ?>">Previous</a>
            <?php endif; ?>

            <span class="pagination__status">Page <?= e($results['page']) ?> of <?= e($results['last_page']) ?></span>

            <?php if ($results['page'] < $results['last_page']): ?>
                <a class="pagination__link" href="<?= url('admin/jobs', array_merge($baseQuery, ['page' => $results['page'] + 1])) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
