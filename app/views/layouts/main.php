<?php
/**
 * Main layout: top navigation, content, footer.
 *
 * Used by the public pages, the job views and everything a job seeker sees.
 * Employer and admin screens use layouts/dashboard.php instead.
 *
 * Available: $content, $title, $user, $unreadCount
 */
$pageTitle = isset($title) && $title !== '' ? $title . ' · ' . app_name() : app_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Find your next role, or hire the right people. Browse thousands of open positions on <?= e(app_name()) ?>.">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?= \App\Core\View::stack('head') ?>
</head>
<body class="page page--default">
    <a class="skip-link" href="#main">Skip to content</a>

    <?= \App\Core\View::partial('header') ?>

    <main class="site-main" id="main">
        <?php if (!empty($breadcrumbs)): ?>
            <nav class="breadcrumbs container" aria-label="Breadcrumb">
                <?= implode('', array_map(function ($crumb) { return '<span>' . e($crumb) . '</span>'; }, $breadcrumbs)) ?>
            </nav>
        <?php endif; ?>

        <div class="container">
            <?= render_flashes() ?>
        </div>

        <?= $content ?>
    </main>

    <?= \App\Core\View::partial('footer') ?>

    <script src="<?= asset('js/app.js') ?>"></script>
    <?= \App\Core\View::stack('scripts') ?>
</body>
</html>
