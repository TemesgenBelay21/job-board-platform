<?php
/**
 * Login form.
 */
?>
<div class="form-card">
    <header class="form-card__header">
        <h1>Welcome back</h1>
        <p>Log in to manage your applications or your job listings.</p>
    </header>

    <form method="post" action="<?= url('auth/login') ?>" data-once>
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="email">Email address <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" maxlength="190" autofocus>
        </div>

        <div class="field">
            <label class="field__label" for="password">Password <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <div class="form-actions form-actions--end">
            <a class="btn btn--ghost" href="<?= url('auth/forgot-password') ?>">Forgot password?</a>
            <button class="btn btn--primary" type="submit">Log in</button>
        </div>
    </form>

    <p class="form-card__footer">
        New here? <a href="<?= url('auth/register') ?>">Create a free account</a>
    </p>
</div>
