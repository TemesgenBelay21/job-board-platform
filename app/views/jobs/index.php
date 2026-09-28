<?php
/**
 * Public job search: filters on the left, results on the right.
 *
 * Expects: $results, $filters, $categories, $jobTypes, $sorts
 * Optional: $savedJobIds, $appliedJobIds for a signed in seeker
 */
$typeLabels = [
    'full_time' => 'Full time',
    'part_time' => 'Part time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'remote' => 'Remote',
];

$savedJobIds = isset($savedJobIds) ? $savedJobIds : [];
$appliedJobIds = isset($appliedJobIds) ? $appliedJobIds : [];

// Keep the current filters in the pager links.
$baseQuery = array_filter([
    'q' => $filters['q'],
    'category' => $filters['category'],
    'job_type' => $filters['job_type'],
    'location' => $filters['location'],
    'salary_min' => $filters['salary_min'],
    'sort' => $filters['sort'] !== \App\Models\Job::DEFAULT_SORT ? $filters['sort'] : '',
], 'strlen');

$hasFilters = !empty($baseQuery);
$currentUser = isset($user) ? $user : null;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Browse jobs</h1>
        <p class="page-head__subtitle">
            <?php if ($results['total'] > 0): ?>
                <?= e(number_format($results['total'])) ?> open <?= $results['total'] === 1 ? 'position' : 'positions' ?>
                <?php if ($hasFilters): ?>match your search<?php endif; ?>.
            <?php else: ?>
                No open positions right now.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="browse">
    <aside class="browse__filters">
        <form method="get" action="<?= url('jobs') ?>" class="filter-form">
            <div class="field">
                <label class="field__label" for="q">Keywords</label>
                <input class="input" type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="PHP, designer, Addis">
            </div>

            <div class="field">
                <label class="field__label" for="category">Category</label>
                <select class="input" id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"<?= (int) $filters['category'] === (int) $category['id'] ? ' selected' : '' ?>>
                            <?= e($category['name']) ?> (<?= e($category['open_job_count']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label class="field__label" for="job_type">Job type</label>
                <select class="input" id="job_type" name="job_type">
                    <option value="">Any type</option>
                    <?php foreach ($jobTypes as $type): ?>
                        <option value="<?= e($type) ?>"<?= $filters['job_type'] === $type ? ' selected' : '' ?>>
                            <?= e(isset($typeLabels[$type]) ? $typeLabels[$type] : humanize($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label class="field__label" for="location">Location</label>
                <input class="input" type="text" id="location" name="location" value="<?= e($filters['location']) ?>" placeholder="Addis Ababa">
            </div>

            <div class="field">
                <label class="field__label" for="salary_min">Minimum salary (ETB)</label>
                <input class="input" type="number" id="salary_min" name="salary_min" value="<?= e($filters['salary_min']) ?>" min="0" step="1000" placeholder="30000">
            </div>

            <div class="form-actions form-actions--stack">
                <button class="btn btn--primary" type="submit">Apply filters</button>
                <?php if ($hasFilters): ?>
                    <a class="btn btn--ghost" href="<?= url('jobs') ?>">Clear all</a>
                <?php endif; ?>
            </div>
        </form>
    </aside>

    <div class="browse__results">
        <?php if ($results['total'] > 0): ?>
            <form method="get" action="<?= url('jobs') ?>" class="sort-form">
                <?php foreach ($baseQuery as $key => $value): ?>
                    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                <?php endforeach; ?>
                <label class="sort-form__label" for="sort">Sort by</label>
                <select class="input input--sm" id="sort" name="sort" data-auto-submit>
                    <?php foreach ($sorts as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $filters['sort'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn--secondary btn--sm" type="submit">Sort</button></noscript>
            </form>

            <div class="job-list">
                <?php foreach ($results['rows'] as $job): ?>
                    <?php
                    $jobId = (int) $job['id'];
                    $saved = in_array($jobId, $savedJobIds, true);
                    $applied = in_array($jobId, $appliedJobIds, true);
                    $showSaveButton = $currentUser !== null && $currentUser['role'] === 'seeker';
                    ?>
                    <?= \App\Core\View::partial('jobs/card', [
                        'job' => $job,
                        'showSaveButton' => $showSaveButton,
                        'saved' => $saved,
                        'applied' => $applied,
                        'returnTo' => url('jobs', $baseQuery),
                    ]) ?>
                <?php endforeach; ?>
            </div>

            <?php if ($results['last_page'] > 1): ?>
                <nav class="pagination" aria-label="Pagination">
                    <?php if ($results['page'] > 1): ?>
                        <a class="pagination__link" href="<?= url('jobs', array_merge($baseQuery, ['page' => $results['page'] - 1])) ?>">Previous</a>
                    <?php endif; ?>

                    <span class="pagination__status">Page <?= e($results['page']) ?> of <?= e($results['last_page']) ?></span>

                    <?php if ($results['page'] < $results['last_page']): ?>
                        <a class="pagination__link" href="<?= url('jobs', array_merge($baseQuery, ['page' => $results['page'] + 1])) ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <h2 class="empty-state__title">No jobs match your search</h2>
                <p class="empty-state__text">Try fewer keywords, or widen the filters.</p>
                <a class="btn btn--primary" href="<?= url('jobs') ?>">Clear filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>
