<?php
/**
 * New-password form, reached from a link in a reset email.
 *
 * @var string $token The reset token, carried through the form as a hidden field
 */
?>
<div class="form-card">
    <header class="form-card__header">
        <h1>Choose a new password</h1>
        <p>Pick something you have not used here before. At least 8 characters, with a letter and a number.</p>
    </header>

    <form method="post" action="<?= url('auth/reset-password') ?>" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="field">
            <label class="field__label" for="password">New password <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="password" id="password" name="password" required autocomplete="new-password" autofocus>
            <p class="field__hint">At least 8 characters, including a letter and a number.</p>
        </div>

        <div class="field">
            <label class="field__label" for="password_confirmation">Confirm new password <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="form-actions">
            <a class="btn btn--ghost" href="<?= url('auth/login') ?>">Cancel</a>
            <button class="btn btn--primary" type="submit">Change password</button>
        </div>
    </form>
</div>
