<?php
/**
 * Shared markup for every error page.
 *
 * Expects: $status, $title, $message and an optional $debug array.
 */
$status = isset($status) ? $status : 500;
?>
<section class="error-page">
    <p class="error-page__code"><?= e($status) ?></p>
    <h1 class="error-page__title"><?= isset($title) && $title !== '' ? e($title) : 'Something went wrong' ?></h1>
    <p class="error-page__message">
        <?= isset($message) && $message !== '' ? e($message) : 'We could not complete that request.' ?>
    </p>

    <a class="btn btn--primary" href="<?= url('/') ?>">Back to home</a>

    <?php if (!empty($debug)): ?>
        <details class="error-page__debug">
            <summary>Debug details</summary>
            <pre><?= e($debug['type'] . "\n" . $debug['file'] . ':' . $debug['line'] . "\n\n" . $debug['trace']) ?></pre>
        </details>
    <?php endif; ?>
</section>
