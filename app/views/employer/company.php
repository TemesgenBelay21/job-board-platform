<?php
/**
 * The employer company profile form and logo.
 *
 * Expects: $company
 */
$logoUrl = !empty($company['logo']) ? url('uploads/logos/' . $company['logo']) : null;
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Company profile</h1>
        <p class="page-head__subtitle">This is what candidates see on your listings and your public page.</p>
    </div>

    <?php if ($company['id']): ?>
        <a class="btn btn--secondary" href="<?= url('companies/' . (int) $company['id']) ?>">View public page</a>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="card__title">Logo</h2>

    <?php if ($logoUrl !== null): ?>
        <div class="file-row">
            <div class="logo-preview">
                <span class="company-logo company-logo--lg" aria-hidden="true">
                    <img src="<?= e($logoUrl) ?>" alt="" width="56" height="56" loading="lazy">
                </span>
                <p class="file-row__name"><?= e($company['logo']) ?></p>
            </div>

            <form method="post" action="<?= url('employer/company/logo/delete') ?>" data-confirm="Remove your logo?">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="<?= e(url('employer/company')) ?>">
                <button class="btn btn--danger btn--small" type="submit">Remove</button>
            </form>
        </div>
    <?php else: ?>
        <p class="card__meta">
            No logo yet. Without one we show your company initials instead.
        </p>
    <?php endif; ?>

    <form method="post" action="<?= url('employer/company/logo') ?>" enctype="multipart/form-data" data-once class="mt-4">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="<?= e(url('employer/company')) ?>">

        <div class="field">
            <label class="field__label" for="logo"><?= $logoUrl !== null ? 'Replace logo' : 'Upload a logo' ?></label>
            <input class="input" type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.gif,.webp"
                   <?= $logoUrl === null ? 'required' : '' ?> data-file-input>
            <p class="field__hint">
                PNG, JPG, GIF or WebP, up to 2 MB. A square image works best.
                <?= $logoUrl !== null ? 'Your current logo is kept if the upload fails.' : '' ?>
            </p>
        </div>

        <div class="form-actions">
            <button class="btn btn--secondary" type="submit">Upload logo</button>
        </div>
    </form>
</div>

<form method="post" action="<?= url('employer/company') ?>" class="form-card">
    <?= csrf_field() ?>

    <h2 class="card__title">Company details</h2>

    <div class="field">
        <label class="field__label" for="company_name">Company name <span class="field__required" aria-hidden="true">*</span></label>
        <input class="input" type="text" id="company_name" name="company_name" maxlength="150" required
               value="<?= e(old('company_name', $company['company_name'])) ?>">
    </div>

    <div class="field">
        <label class="field__label" for="website">Website</label>
        <input class="input" type="text" id="website" name="website" maxlength="255"
               value="<?= e(old('website', $company['website'])) ?>" placeholder="example.com">
        <p class="field__hint">Stored without a scheme and linked as https on your public page.</p>
    </div>

    <div class="field">
        <label class="field__label" for="location">Location</label>
        <input class="input" type="text" id="location" name="location" maxlength="150"
               value="<?= e(old('location', $company['location'])) ?>" placeholder="Addis Ababa, Ethiopia">
    </div>

    <div class="field">
        <label class="field__label" for="description">About the company</label>
        <textarea class="input" id="description" name="description" rows="6" maxlength="3000"><?= e(old('description', $company['description'])) ?></textarea>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit" data-once>Save profile</button>
    </div>
</form>
