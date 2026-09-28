<?php
/**
 * The employer's applicants, with a job and status filter.
 *
 * Expects: $company, $results, $counts, $jobs, $selectedJob (array|null),
 *          $activeStatus, $statusLabels
 */
$labels = isset($statusLabels) ? $statusLabels : [];
$active = isset($activeStatus) ? $activeStatus : '';
$selected = isset($selectedJob) ? $selectedJob : null;
$rows = $results['rows'];
$total = isset($counts) ? array_sum($counts) : 0;

/* The filter links have to carry the job, so paging and status changes stay
   inside the listing the employer is looking at. */
$baseQuery = [];
if ($selected !== null) {
    $baseQuery['job'] = (int) $selected['id'];
}
if ($active !== '') {
    $baseQuery['status'] = $active;
}
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Applicants</h1>
        <p class="page-head__subtitle">
            <?= e($selected !== null ? 'For ' . $selected['title'] : 'Across all your listings') ?>.
        </p>
    </div>
</div>

<?php if ($selected !== null): ?>
    <nav class="filter-pills" aria-label="Choose a listing">
        <a class="filter-pill<?= $selected === null ? ' is-active' : '' ?>" href="<?= url('employer/applicants', $active === '' ? [] : ['status' => $active]) ?>">
            All listings
        </a>
        <?php foreach ($jobs as $job): ?>
            <?php $id = (int) $job['id']; ?>
            <a class="filter-pill<?= $selected !== null && (int) $selected['id'] === $id ? ' is-active' : '' ?>"
               href="<?= url('employer/applicants', $active === '' ? ['job' => $id] : ['job' => $id, 'status' => $active]) ?>">
                <?= e($job['title']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<nav class="filter-pills" aria-label="Filter by status">
    <a class="filter-pill<?= $active === '' ? ' is-active' : '' ?>" href="<?= url('employer/applicants', $baseQuery) ?>">
        All <span class="filter-pill__count"><?= e($total) ?></span>
    </a>
    <?php foreach ($labels as $key => $label): ?>
        <a class="filter-pill<?= $active === $key ? ' is-active' : '' ?>"
           href="<?= url('employer/applicants', array_merge($baseQuery, ['status' => $key])) ?>">
            <?= e($label) ?> <span class="filter-pill__count"><?= e(isset($counts[$key]) ? $counts[$key] : 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No applicants yet</h2>
        <p class="empty-state__text">
            <?php if (empty($jobs)): ?>
                Post a listing and applicants will show up here.
            <?php else: ?>
                Nobody has applied with this filter yet.
            <?php endif; ?>
        </p>
        <?php if (empty($jobs)): ?>
            <a class="btn btn--primary" href="<?= url('employer/jobs/create') ?>">Post a job</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Applicant</th>
                    <th scope="col">Job</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $application): ?>
                    <?php
                    $id = (int) $application['id'];
                    $returnTo = url('employer/applicants', $baseQuery);
                    $hasResume = !empty($application['resume_path']) || !empty($application['profile_resume_path']);
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($application['seeker_name']) ?></strong>
                            <p class="card__meta"><?= e($application['seeker_email']) ?></p>
                            <?php if (!empty($application['seeker_headline'])): ?>
                                <p class="card__meta"><?= e($application['seeker_headline']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= url('jobs/' . (int) $application['job_id']) ?>"><?= e($application['title']) ?></a>
                            <p class="card__meta"><?= e($application['company_name']) ?></p>
                        </td>
                        <td class="num"><?= e(format_date($application['applied_at'])) ?></td>
                        <td>
                            <span class="badge badge--<?= e($application['status']) ?>">
                                <?= e(isset($labels[$application['status']]) ? $labels[$application['status']] : $application['status']) ?>
                            </span>
                        </td>
                        <td class="table__actions">
                            <?php if ($hasResume): ?>
                                <a class="btn btn--ghost btn--small" href="<?= url('employer/applications/' . $id . '/resume') ?>">CV</a>
                            <?php endif; ?>

                            <form method="post" action="<?= url('employer/applications/' . $id . '/status') ?>" class="status-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                <label class="visually-hidden" for="status-<?= $id ?>">Status for <?= e($application['seeker_name']) ?></label>
                                <select class="input input--sm" id="status-<?= $id ?>" name="status">
                                    <?php foreach ($labels as $key => $label): ?>
                                        <option value="<?= e($key) ?>"<?= $application['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--secondary btn--small" type="submit" data-once>Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($results['last_page'] > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($results['page'] > 1): ?>
                <a class="pagination__link" href="<?= url('employer/applicants', array_merge($baseQuery, ['page' => $results['page'] - 1])) ?>">Previous</a>
            <?php endif; ?>

            <span class="pagination__status">Page <?= e($results['page']) ?> of <?= e($results['last_page']) ?></span>

            <?php if ($results['page'] < $results['last_page']): ?>
                <a class="pagination__link" href="<?= url('employer/applicants', array_merge($baseQuery, ['page' => $results['page'] + 1])) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
