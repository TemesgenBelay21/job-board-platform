<?php
/**
 * Bare layout used by error pages: no navigation, no scripts.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($title) && $title !== '' ? e($title) . ' · ' : '' ?><?= e(\App\Core\App::config('app.name', 'JobBoard')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="page-error">
    <?= $content ?>
</body>
</html>
