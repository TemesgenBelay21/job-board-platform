<?php
/**
 * Compare the class names used in views against the ones the stylesheets define.
 * Run from the project root: php tools/check-classes.php
 */
$root = dirname(__DIR__);

$css = '';
foreach (glob($root . '/public/assets/css/*.css') as $file) {
    $css .= file_get_contents($file);
}

preg_match_all('/\.([a-zA-Z][a-zA-Z0-9_-]*)/', $css, $matches);
$defined = array_flip(array_unique($matches[1]));

$used = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app/views'));

foreach ($iterator as $file) {
    if ($file->isDir() || substr($file->getFilename(), -4) !== '.php') {
        continue;
    }

    $html = file_get_contents($file->getPathname());

    /* Only static class attributes. An attribute holding PHP is skipped: a
       fragment such as class="nav " . active_class('x') cannot be resolved
       without running the view, and counting the code as a class name would
       report false positives. */
    preg_match_all('/class="([^"{$?]*)"/', $html, $found);

    foreach ($found[1] as $list) {
        foreach (preg_split('/\s+/', trim($list)) as $class) {
            if ($class === '') {
                continue;
            }

            $used[$class][] = $file->getFilename();
        }
    }
}

ksort($used);

$missing = [];

foreach ($used as $class => $files) {
    if (!isset($defined[$class])) {
        $missing[$class] = array_count_values($files);
    }
}

echo 'Classes used in views but not defined in CSS: ' . count($missing) . "\n\n";

foreach ($missing as $class => $where) {
    $places = array_map(function ($name, $count) {
        return $name . ($count > 1 ? " ($count)" : '');
    }, array_keys($where), $where);

    printf("  %-34s %s\n", $class, implode(', ', $places));
}
