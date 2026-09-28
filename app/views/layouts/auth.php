<?php
/**
 * Centred layout for authentication screens.
 *
 * Available: $content, $title
 */
$pageTitle = isset($title) && $title !== '' ? $title . ' · ' . app_name() : app_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?= \App\Core\View::stack('head') ?>
</head>
<body class="page page--auth">
    <div class="auth-shell">
        <a class="brand brand--centered" href="<?= url('/') ?>">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"></rect>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </span>
            <span class="brand__name"><?= e(app_name()) ?></span>
        </a>

        <div class="container container--narrow">
            <?= render_flashes() ?>
            <?= $content ?>
        </div>

        <p class="auth-shell__back"><a href="<?= url('/') ?>">&larr; Back to home</a></p>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?= \App\Core\View::stack('scripts') ?>
</body>
</html>
