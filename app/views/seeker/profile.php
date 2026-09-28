<?php
/**
 * Seeker profile: the details an employer reads, plus the resume.
 *
 * Expects: $profile, $skills, $statusCounts, $applicationCount, $savedCount,
 *          $resumeName (string|null)
 */
$counts = isset($statusCounts) ? $statusCounts : [];
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">My profile</h1>
        <p class="page-head__subtitle">This is what employers see when they open your application.</p>
    </div>
</div>

<div class="stat-row">
    <a class="stat-card" href="<?= url('seeker/applications') ?>">
        <span class="stat-card__value"><?= e($applicationCount) ?></span>
        <span class="stat-card__label">Applications</span>
    </a>
    <a class="stat-card" href="<?= url('seeker/saved-jobs') ?>">
        <span class="stat-card__value"><?= e($savedCount) ?></span>
        <span class="stat-card__label">Saved jobs</span>
    </a>
    <a class="stat-card" href="<?= url('seeker/applications', ['status' => 'shortlisted']) ?>">
        <span class="stat-card__value"><?= e(isset($counts['shortlisted']) ? $counts['shortlisted'] : 0) ?></span>
        <span class="stat-card__label">Shortlisted</span>
    </a>
</div>

<div class="card">
    <h2 class="card__title">Your details</h2>

    <form method="post" action="<?= url('profile') ?>" data-once>
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="name">Full name <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="text" id="name" name="name" value="<?= e(old('name', $profile['name'])) ?>" required autocomplete="name" maxlength="120">
        </div>

        <div class="field">
            <label class="field__label" for="email">Email address</label>
            <input class="input" type="email" id="email" value="<?= e($profile['email']) ?>" disabled>
            <p class="field__hint">
                Your sign in address. <a href="<?= url('auth/forgot-password') ?>">Change it</a> with a password reset.
            </p>
        </div>

        <div class="field">
            <label class="field__label" for="headline">Headline</label>
            <input class="input" type="text" id="headline" name="headline" value="<?= e(old('headline', $profile['headline'])) ?>" maxlength="160" placeholder="Backend developer, 4 years with PHP">
        </div>

        <div class="field-row">
            <div class="field">
                <label class="field__label" for="phone">Phone</label>
                <input class="input" type="text" id="phone" name="phone" value="<?= e(old('phone', $profile['phone'])) ?>" maxlength="40" autocomplete="tel">
            </div>

            <div class="field">
                <label class="field__label" for="location">Location</label>
                <input class="input" type="text" id="location" name="location" value="<?= e(old('location', $profile['location'])) ?>" maxlength="150" placeholder="Addis Ababa">
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="skills">Skills</label>
            <input class="input" type="text" id="skills" name="skills" value="<?= e(old('skills', $profile['skills'])) ?>" maxlength="500" placeholder="PHP, MySQL, JavaScript">
            <p class="field__hint">Separate with commas.</p>
        </div>

        <div class="field">
            <label class="field__label" for="bio">About you</label>
            <textarea class="input" id="bio" name="bio" rows="6" maxlength="2000" placeholder="A short summary of your experience and what you are looking for."><?= e(old('bio', $profile['bio'])) ?></textarea>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">Save profile</button>
        </div>
    </form>
</div>

<div class="card">
    <h2 class="card__title">Resume</h2>

    <?php if (!empty($skills)): ?>
        <p class="card__meta">Skills on your profile: <?= e(implode(', ', $skills)) ?></p>
    <?php endif; ?>

    <?php if (!empty($resumeName)): ?>
        <div class="file-row">
            <div>
                <p class="file-row__name"><?= e($resumeName) ?></p>
                <p class="file-row__size"><?= e(\App\Helpers\Resumes::size($resumeName)) ?> · PDF or DOCX</p>
            </div>
            <form method="post" action="<?= url('profile/resume/delete') ?>" data-confirm="Remove your resume?">
                <?= csrf_field() ?>
                <button class="btn btn--danger btn--small" type="submit">Remove</button>
            </form>
        </div>

        <form method="post" action="<?= url('profile/resume') ?>" enctype="multipart/form-data" data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="resume">Replace it</label>
                <input class="input" type="file" id="resume" name="resume" accept=".pdf,.docx" data-file-input>
                <p class="field__hint">
                    Up to <?= e(round(\App\Core\App::config('uploads.resume_max_size', 2097152) / 1024)) ?> KB.
                    Your current file is kept if the upload fails.
                </p>
            </div>
            <div class="form-actions">
                <button class="btn btn--secondary" type="submit">Upload new resume</button>
            </div>
        </form>
    <?php else: ?>
        <p class="card__meta">You have not uploaded a resume yet. Employers cannot see your experience without one.</p>

        <form method="post" action="<?= url('profile/resume') ?>" enctype="multipart/form-data" data-once>
            <?= csrf_field() ?>
            <div class="field">
                <label class="field__label" for="resume">Resume file <span class="field__required" aria-hidden="true">*</span></label>
                <input class="input" type="file" id="resume" name="resume" accept=".pdf,.docx" required data-file-input>
                <p class="field__hint">
                    PDF or DOCX, up to <?= e(round(\App\Core\App::config('uploads.resume_max_size', 2097152) / 1024)) ?> KB.
                </p>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Upload resume</button>
            </div>
        </form>
    <?php endif; ?>
</div>
