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

/* Class prefixes that a view finishes at runtime, e.g. badge--<status>. Their
   final name is unknowable here, so instead of guessing we list the prefix and
   check that the stylesheet covers it with at least one rule. A dynamic class
   that was never styled is invisible to a plain used-versus-defined diff. */
$dynamic = [];

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

    /* The static pass above skipped any attribute containing PHP. Catch the
       common case where a BEM class is completed by an expression, such as
       class="badge badge--<?= $status ?>", and record the prefix. */
    preg_match_all('/\b([a-z][a-z0-9_-]*)--<\?=/i', $html, $dynamicFound);

    foreach ($dynamicFound[1] as $prefix) {
        $dynamic[$prefix][] = $file->getFilename();
    }
}

ksort($used);
ksort($dynamic);

$missing = [];

foreach ($used as $class => $files) {
    if (!isset($defined[$class])) {
        $missing[$class] = array_count_values($files);
    }
}

echo 'Classes used in views but not defined in CSS: ' . count($missing) . "\n\n";

foreach ($missing as $class => $where) {
    $places = array_map(function ($name, $count) {
        return $name . ' (' . $count . ')';
    }, array_keys($where), $where);

    printf("  %-34s %s\n", $class, implode(', ', $places));
}

/* A dynamic prefix is covered when at least one rule exists for it, but that
   only proves one of its possible values is styled. Flag it for a human look
   rather than treating it as passing. */
$unstyled = array_filter(array_keys($dynamic), function ($prefix) use ($defined) {
    foreach (array_keys($defined) as $class) {
        if (strpos($class, $prefix . '--') === 0) {
            return false;
        }
    }

    return true;
});

echo "\nClass prefixes completed at runtime: " . count($dynamic)
    . ' (' . count($unstyled) . " with no rule at all)\n";

foreach ($dynamic as $prefix => $files) {
    $variants = 0;

    foreach (array_keys($defined) as $class) {
        if (strpos($class, $prefix . '--') === 0) {
            $variants++;
        }
    }

    printf(
        "  %-34s %d rule%s  in %s\n",
        $prefix . '--*',
        $variants,
        $variants === 1 ? '' : 's',
        implode(', ', array_unique($files))
    );
}

exit(empty($missing) && empty($unstyled) ? 0 : 1);
