<?php
/**
 * A single job listing card, reused by the home page, browse results, saved
 * jobs and the company page.
 *
 * Expects: $job (a row from Job::LISTING_COLUMNS)
 * Optional: $saved (bool) to show the saved state, $showSaveButton (bool)
 */

use App\Helpers\Resumes;

$jobTypeLabels = [
    'full_time' => 'Full time',
    'part_time' => 'Part time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'remote' => 'Remote',
];

$salary = '';
if ($job['salary_min'] !== null || $job['salary_max'] !== null) {
    $min = $job['salary_min'] !== null ? number_format((int) $job['salary_min']) : null;
    $max = $job['salary_max'] !== null ? number_format((int) $job['salary_max']) : null;

    if ($min !== null && $max !== null) {
        $salary = $min === $max ? $min : $min . ' - ' . $max;
    } else {
        $salary = ($min !== null ? 'From ' . $min : 'Up to ' . $max);
    }
}

$hasSalary = $salary !== '';
$deadline = $job['deadline'] !== null ? strtotime($job['deadline']) : null;
$isExpired = $deadline !== null && $deadline < strtotime('today');
?>
<article class="job-card">
    <div class="job-card__top">
        <div>
            <h3 class="job-card__title">
                <a href="<?= url('jobs/' . (int) $job['id']) ?>"><?= e($job['title']) ?></a>
            </h3>
            <p class="job-card__company">
                <a href="<?= url('companies/' . (int) $job['company_id']) ?>"><?= e($job['company_name']) ?></a>
            </p>
        </div>

        <span class="company-logo company-logo--sm" aria-hidden="true">
            <?php if (!empty($job['company_logo'])): ?>
                <img src="<?= url('uploads/logos/' . e($job['company_logo'])) ?>" alt="" width="36" height="36" loading="lazy">
            <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($job['company_name'], 0, 1))) ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="job-card__meta">
        <span>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <?= e($job['location']) ?>
        </span>
        <span>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
            </svg>
            <?= e(isset($jobTypeLabels[$job['job_type']]) ? $jobTypeLabels[$job['job_type']] : humanize($job['job_type'])) ?>
        </span>
        <?php if (!empty($job['category_name'])): ?>
            <span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
                <?= e($job['category_name']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="job-card__footer">
        <?php if ($hasSalary): ?>
            <span class="job-card__salary"><?= e($salary) ?> ETB</span>
        <?php else: ?>
            <span class="job-card__salary job-card__salary--hidden">Salary not disclosed</span>
        <?php endif; ?>

        <div class="btn-row">
            <?php if (!empty($showSaveButton) && !empty($user) && $user['role'] === 'seeker'): ?>
                <form method="post" action="<?= url('seeker/saved-jobs/toggle') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                    <input type="hidden" name="return_to" value="<?= e(isset($returnTo) ? $returnTo : url('jobs')) ?>">
                    <button type="submit" class="btn btn--secondary btn--sm">
                        <?= !empty($saved) ? 'Saved' : 'Save job' ?>
                    </button>
                </form>
            <?php endif; ?>
            <a class="btn btn--primary btn--sm" href="<?= url('jobs/' . (int) $job['id']) ?>">
                <?= !empty($applied) ? 'View application' : 'View job' ?>
            </a>
        </div>
    </div>

    <?php if ($isExpired): ?>
        <p class="text-sm mb-0" style="color: var(--color-warning);">
            Applications closed on <?= e(date('j M Y', $deadline)) ?>
        </p>
    <?php endif; ?>
</article>
