<?php
/**
 * A single job listing with the apply form or the seeker's application.
 *
 * Expects: $job, $category, $isOpen, $relatedJobs, $application, $profile,
 *          $savedJobIds
 */
$typeLabels = [
    'full_time' => 'Full time',
    'part_time' => 'Part time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'remote' => 'Remote',
];

$statusLabels = [
    'pending' => 'Awaiting approval',
    'open' => 'Open',
    'closed' => 'Closed',
];

$jobId = (int) $job['id'];
$isSeeker = $user !== null && $user['role'] === 'seeker';
$isSaved = in_array($jobId, $savedJobIds, true);
$isApplied = $application !== null;
$hasResume = $profile !== null && !empty($profile['resume_path']);
$deadline = $job['deadline'] !== null ? strtotime($job['deadline']) : null;
?>
<article class="job-detail">
    <header class="job-detail__header">
        <div class="job-detail__intro">
            <p class="job-detail__eyebrow">
                <a href="<?= url('companies/' . (int) $job['company_id']) ?>"><?= e($job['company_name']) ?></a>
                <?php if (!empty($category)): ?>
                    &middot; <a href="<?= url('jobs', ['category' => (int) $job['category_id']]) ?>"><?= e($category) ?></a>
                <?php endif; ?>
            </p>

            <h1 class="job-detail__title"><?= e($job['title']) ?></h1>

            <ul class="job-detail__facts">
                <li><?= e($job['location']) ?></li>
                <li><?= e(isset($typeLabels[$job['job_type']]) ? $typeLabels[$job['job_type']] : humanize($job['job_type'])) ?></li>
                <?php if ($job['salary_min'] !== null || $job['salary_max'] !== null): ?>
                    <li>
                        <?php
                        $min = $job['salary_min'] !== null ? number_format((int) $job['salary_min']) : null;
                        $max = $job['salary_max'] !== null ? number_format((int) $job['salary_max']) : null;
                        echo $min !== null && $max !== null
                            ? e(($min === $max ? $min : $min . ' - ' . $max) . ' ETB')
                            : e(($min !== null ? 'From ' . $min : 'Up to ' . $max) . ' ETB');
                        ?>
                    </li>
                <?php endif; ?>
                <li>Posted <?= e(format_date($job['created_at'])) ?></li>
            </ul>
        </div>

        <span class="company-logo company-logo--lg" aria-hidden="true">
            <?php if (!empty($job['company_logo'])): ?>
                <img src="<?= url('uploads/logos/' . e($job['company_logo'])) ?>" alt="" width="56" height="56" loading="lazy">
            <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($job['company_name'], 0, 1))) ?>
            <?php endif; ?>
        </span>
    </header>

    <?php if (!$isOpen): ?>
        <p class="alert alert--warning">
            This listing is <?= e(isset($statusLabels[$job['status']]) ? $statusLabels[$job['status']] : $job['status']) ?><?php
                if ($deadline !== null && $deadline < strtotime('today')): ?> &mdash; applications closed <?= e(format_date($job['deadline'])) ?><?php
                endif; ?>.
        </p>
    <?php endif; ?>

    <div class="job-detail__body">
        <div class="job-detail__content">
            <h2 class="job-detail__section">About this role</h2>
            <div class="prose"><?= nl2br(e($job['description'])) ?></div>

            <?php if (!empty($job['requirements'])): ?>
                <h2 class="job-detail__section">What you will need</h2>
                <div class="prose"><?= nl2br(e($job['requirements'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($relatedJobs)): ?>
                <h2 class="job-detail__section">Similar openings</h2>
                <ul class="link-list">
                    <?php foreach ($relatedJobs as $related): ?>
                        <li>
                            <a href="<?= url('jobs/' . (int) $related['id']) ?>"><?= e($related['title']) ?></a>
                            <span class="text-sm text-muted"><?= e($related['company_name']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <aside class="job-detail__aside">
            <div class="card card--sticky">
                <?php if ($isApplied): ?>
                    <h2 class="card__title">You applied</h2>
                    <p class="card__meta">Sent <?= e(format_date($application['applied_at'])) ?></p>
                    <p>
                        <span class="badge badge--<?= e($application['status']) ?>">
                            <?= e(humanize($application['status'])) ?>
                        </span>
                    </p>
                    <a class="btn btn--secondary btn--block" href="<?= url('seeker/applications') ?>">Track application</a>

                    <?php if (in_array($application['status'], ['submitted', 'under_review'], true)): ?>
                        <form method="post" action="<?= url('seeker/applications/' . (int) $application['id'] . '/withdraw') ?>" data-confirm="Withdraw this application?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="return_to" value="<?= e(url('jobs/' . $jobId)) ?>">
                            <button class="btn btn--ghost btn--block" type="submit">Withdraw</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (!$isSeeker): ?>
                    <h2 class="card__title">Interested?</h2>
                    <p class="card__meta">Create a free seeker account to apply in one click.</p>
                    <a class="btn btn--primary btn--block" href="<?= url('auth/register', ['role' => 'seeker']) ?>">Create an account</a>
                    <a class="btn btn--ghost btn--block" href="<?= url('auth/login', ['return_to' => url('jobs/' . $jobId)]) ?>">Log in</a>
                <?php elseif (!$hasResume): ?>
                    <h2 class="card__title">One step left</h2>
                    <p class="card__meta">Upload a resume so the employer can see your experience.</p>
                    <a class="btn btn--primary btn--block" href="<?= url('profile') ?>">Upload my resume</a>
                <?php else: ?>
                    <h2 class="card__title">Apply now</h2>
                    <p class="card__meta">Your profile resume will be sent with this application.</p>

                    <form method="post" action="<?= url('jobs/' . $jobId . '/apply') ?>" enctype="multipart/form-data" data-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= e(url('jobs/' . $jobId)) ?>">

                        <div class="field">
                            <label class="field__label" for="cover_letter">Cover letter</label>
                            <textarea class="input" id="cover_letter" name="cover_letter" rows="6" maxlength="3000" placeholder="Why are you a good fit for this role?"><?= e(old('cover_letter')) ?></textarea>
                        </div>

                        <details class="field">
                            <summary class="field__label">Send a different resume</summary>
                            <input class="input" type="file" name="resume" accept=".pdf,.docx" data-file-input>
                            <p class="field__hint">Optional. PDF or DOCX.</p>
                        </details>

                        <button class="btn btn--primary btn--block" type="submit">Send application</button>
                    </form>

                    <form method="post" action="<?= url('seeker/saved-jobs/toggle') ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="return_to" value="<?= e(url('jobs/' . $jobId)) ?>">
                        <button class="btn btn--ghost btn--block" type="submit">
                            <?= $isSaved ? 'Remove from saved jobs' : 'Save this job' ?>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($deadline !== null): ?>
                    <p class="card__foot">
                        Applications close <?= e(format_date($job['deadline'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</article>
