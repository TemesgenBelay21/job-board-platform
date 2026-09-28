<?php
/**
 * 404 Not Found
 */
echo \App\Core\View::render('errors/generic', [
    'status' => 404,
    'title' => 'Page not found',
    'message' => 'The page you requested could not be found. It may have been removed, or the link may be wrong.',
    'debug' => isset($debug) ? $debug : null,
], null);
