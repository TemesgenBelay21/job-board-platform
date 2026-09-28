<?php
/**
 * 405 Method Not Allowed
 */
echo \App\Core\View::render('errors/generic', [
    'status' => 405,
    'title' => 'Method not allowed',
    'message' => 'That type of request is not supported for this page.',
    'debug' => isset($debug) ? $debug : null,
], null);
