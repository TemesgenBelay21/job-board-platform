<?php
/**
 * The employer dashboard: listing counts, applicant counts and the most
 * recent activity.
 *
 * Expects: $company, $jobCounts, $applicationCounts, $recentApplications,
 *          $recentJobs
 */
$jobTotal = array_sum($jobCounts);
$applicationTotal = array_sum($applicationCounts);
$newApplications = isset($applicationCounts['submitted']) ? $applicationCounts['submitted'] : 0;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Dashboard</h1>
        <p class="page-head__subtitle"><?= e($company['company_name']) ?></p>
    </div>
    <a class="btn btn--primary" href="<?= url('employer/jobs/create') ?>">Post a job</a>
</div>

<?php if ($jobCounts['pending'] > 0): ?>
    <div class="alert alert--info">
        <?= $jobCounts['pending'] ?> listing<?= $jobCounts['pending'] === 1 ? '' : 's' ?>
        waiting for approval. Pending listings are not visible in the job search yet.
    </div>
<?php endif; ?>

<div class="stat-row">
    <a class="stat-card" href="<?= url('employer/jobs', ['status' => 'open']) ?>">
        <span class="stat-card__value"><?= e($jobCounts['open']) ?></span>
        <span class="stat-card__label">Live listings</span>
    </a>
    <a class="stat-card" href="<?= url('employer/jobs', ['status' => 'pending']) ?>">
        <span class="stat-card__value"><?= e($jobCounts['pending']) ?></span>
        <span class="stat-card__label">Pending approval</span>
    </a>
    <a class="stat-card" href="<?= url('employer/applicants', ['status' => 'submitted']) ?>">
        <span class="stat-card__value"><?= e($newApplications) ?></span>
        <span class="stat-card__label">New applicants</span>
    </a>
    <a class="stat-card" href="<?= url('employer/applicants', ['status' => 'shortlisted']) ?>">
        <span class="stat-card__value"><?= e($applicationTotal) ?></span>
        <span class="stat-card__label">Total applicants</span>
    </a>
</div>

<div class="mt-4">
    <h2 class="card__title">Recent applicants</h2>

    <?php if (empty($recentApplications)): ?>
        <div class="card">
            <p class="card__meta">No applications yet.</p>
            <a class="btn btn--secondary" href="<?= url('employer/jobs') ?>">Review your listings</a>
        </div>
    <?php else: ?>
        <div class="card-list">
            <?php foreach ($recentApplications as $application): ?>
                <article class="card application-card">
                    <div class="application-card__main">
                        <h3 class="card__title">
                            <a href="<?= url('employer/applicants', ['job' => (int) $application['job_id']]) ?>">
                                <?= e($application['seeker_name']) ?>
                            </a>
                        </h3>
                        <p class="card__meta">
                            applied for <a href="<?= url('jobs/' . (int) $application['job_id']) ?>"><?= e($application['title']) ?></a>
                        </p>
                        <p class="card__meta"><?= e(time_ago($application['applied_at'])) ?></p>
                    </div>

                    <div class="application-card__side">
                        <span class="badge badge--<?= e($application['status']) ?>">
                            <?= e(humanize($application['status'])) ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="mt-3"><a href="<?= url('employer/applicants') ?>">See all applicants</a></p>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h2 class="card__title">Your recent listings</h2>

    <?php if (empty($recentJobs)): ?>
        <div class="card">
            <p class="card__meta">You have not posted any jobs yet.</p>
            <a class="btn btn--primary" href="<?= url('employer/jobs/create') ?>">Post your first job</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Job</th>
                        <th scope="col">Status</th>
                        <th scope="col">Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentJobs as $job): ?>
                        <tr>
                            <td>
                                <a href="<?= url('jobs/' . (int) $job['id']) ?>"><?= e($job['title']) ?></a>
                                <p class="card__meta"><?= e($job['location']) ?> &middot; <?= e(humanize($job['job_type'])) ?></p>
                            </td>
                            <td><span class="badge badge--<?= e($job['status']) ?>"><?= e(humanize($job['status'])) ?></span></td>
                            <td class="num"><?= e(format_date($job['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
