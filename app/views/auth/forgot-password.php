<?php
/**
 * Forgot-password form: asks for the email address to send a reset link to.
 */
?>
<div class="form-card">
    <header class="form-card__header">
        <h1>Reset your password</h1>
        <p>Enter the email address on your account and we will send you a link to choose a new password.</p>
    </header>

    <form method="post" action="<?= url('auth/forgot-password') ?>" data-once>
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="email">Email address <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" maxlength="190" autofocus>
        </div>

        <div class="form-actions">
            <a class="btn btn--ghost" href="<?= url('auth/login') ?>">Back to log in</a>
            <button class="btn btn--primary" type="submit">Email me a link</button>
        </div>
    </form>

    <p class="form-card__footer">
        The link works once and expires in <?= e(\App\Models\PasswordReset::LIFETIME_MINUTES) ?> minutes.
    </p>
</div>
