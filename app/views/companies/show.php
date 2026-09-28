<?php
/**
 * Public company page: the profile plus its open listings.
 *
 * Expects: $company, $jobs, $jobCount
 * Optional: $savedJobIds, $appliedJobIds for a signed in seeker
 */
$website = !empty($company['website']) ? $company['website'] : '';
$currentUser = isset($user) ? $user : null;
$savedJobIds = isset($savedJobIds) ? $savedJobIds : [];
$appliedJobIds = isset($appliedJobIds) ? $appliedJobIds : [];
?>
<article class="company-page">
    <header class="company-page__header">
        <span class="company-logo company-logo--xl" aria-hidden="true">
            <?php if (!empty($company['logo'])): ?>
                <img src="<?= url('uploads/logos/' . e($company['logo'])) ?>" alt="" width="80" height="80" loading="lazy">
            <?php else: ?>
                <?= e(\App\Models\CompanyProfile::initial($company['company_name'])) ?>
            <?php endif; ?>
        </span>

        <div>
            <h1 class="page-head__title"><?= e($company['company_name']) ?></h1>
            <p class="page-head__subtitle">
                <?= e($jobCount) ?> open <?= $jobCount === 1 ? 'position' : 'positions' ?>
                <?php if (!empty($company['location'])): ?>
                    &middot; <?= e($company['location']) ?>
                <?php endif; ?>
            </p>

            <?php if ($website !== ''): ?>
                <p class="company-page__site">
                    <a href="<?= e($website) ?>" rel="nofollow noopener external" target="_blank"><?= e($website) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($company['description'])): ?>
        <div class="card">
            <h2 class="card__title">About <?= e($company['company_name']) ?></h2>
            <div class="prose"><?= nl2br(e($company['description'])) ?></div>
        </div>
    <?php endif; ?>

    <h2 class="job-detail__section">
        <?= empty($jobs) ? 'No open positions' : 'Open positions' ?>
    </h2>

    <?php if (empty($jobs)): ?>
        <div class="empty-state">
            <p class="empty-state__text">This company has no open listings right now.</p>
            <a class="btn btn--primary" href="<?= url('jobs') ?>">Browse all jobs</a>
        </div>
    <?php else: ?>
        <div class="job-list">
            <?php foreach ($jobs as $job): ?>
                <?php
                $jobId = (int) $job['id'];
                $showSaveButton = $currentUser !== null && $currentUser['role'] === 'seeker';
                $saved = in_array($jobId, $savedJobIds, true);
                $applied = in_array($jobId, $appliedJobIds, true);
                ?>
                <?= \App\Core\View::partial('jobs/card', [
                    'job' => $job,
                    'showSaveButton' => $showSaveButton,
                    'saved' => $saved,
                    'applied' => $applied,
                    'returnTo' => url('companies/' . (int) $company['id']),
                ]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>
