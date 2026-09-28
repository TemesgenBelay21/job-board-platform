<?php
/**
 * Registration form.
 *
 * Expects: $selectedRole ('seeker'|'employer')
 *
 * The previous choice wins over the ?role= parameter, so a submission that
 * failed validation comes back with the role and company field the user had
 * already picked rather than resetting to the default.
 */
$chosen = old('role');
$role = in_array($chosen, ['seeker', 'employer'], true)
    ? $chosen
    : (isset($selectedRole) && in_array($selectedRole, ['seeker', 'employer'], true)
        ? $selectedRole
        : 'seeker');
?>
<div class="form-card">
    <header class="form-card__header">
        <h1>Create your account</h1>
        <p>Free, and takes less than a minute.</p>
    </header>

    <form method="post" action="<?= url('auth/register') ?>" data-once>
        <?= csrf_field() ?>

        <fieldset style="border:0; padding:0; margin:0 0 var(--space-5);">
            <legend class="field__label" style="padding:0;">I am joining as</legend>

            <div class="role-picker">
                <label class="role-option">
                    <input type="radio" name="role" value="seeker" <?= $role === 'seeker' ? 'checked' : '' ?>>
                    <span class="role-option__title">A job seeker</span>
                    <span class="role-option__text">Search jobs, apply and track your applications.</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="employer" <?= $role === 'employer' ? 'checked' : '' ?>>
                    <span class="role-option__title">An employer</span>
                    <span class="role-option__text">Post listings and review applicants.</span>
                </label>
            </div>
        </fieldset>

        <div class="field">
            <label class="field__label" for="name">Full name <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="text" id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name" maxlength="120">
        </div>

        <div class="field">
            <label class="field__label" for="email">Email address <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email" maxlength="190">
        </div>

        <div class="field" data-company-field <?= $role === 'employer' ? '' : 'hidden' ?>>
            <label class="field__label" for="company_name">Company name <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="text" id="company_name" name="company_name" value="<?= e(old('company_name')) ?>" maxlength="150" autocomplete="organization">
            <span class="field__hint">You can add a description, website and logo later.</span>
        </div>

        <div class="field">
            <label class="field__label" for="password">Password <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
            <span class="field__hint">At least 8 characters, including a letter and a number.</span>
        </div>

        <div class="field">
            <label class="field__label" for="password_confirmation">Confirm password <span class="field__required" aria-hidden="true">*</span></label>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="8">
        </div>

        <div class="form-actions form-actions--end">
            <a class="btn btn--ghost" href="<?= url('auth/login') ?>">I already have an account</a>
            <button class="btn btn--primary" type="submit">Create account</button>
        </div>
    </form>

    <p class="form-card__footer">
        Administrators cannot register here; admin accounts are created directly in the database.
    </p>
</div>

<?php
// Reveal the company field when the employer option is chosen. The field is
// always rendered so the server still receives it; hiding is cosmetic.
\App\Core\View::push('scripts', <<<'JS'
<script>
    (function () {
        var form = document.querySelector('form[action*="auth/register"]');
        if (!form) { return; }

        var companyField = form.querySelector('[data-company-field]');
        var companyInput = form.querySelector('#company_name');

        function sync() {
            var chosen = form.querySelector('input[name="role"]:checked');
            var isEmployer = chosen && chosen.value === 'employer';

            if (companyField) { companyField.hidden = !isEmployer; }
            if (companyInput) { companyInput.required = !!isEmployer; }
        }

        form.querySelectorAll('input[name="role"]').forEach(function (radio) {
            radio.addEventListener('change', sync);
        });

        sync();
    })();
</script>
JS);
