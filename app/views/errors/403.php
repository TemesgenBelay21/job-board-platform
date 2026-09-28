<?php
/**
 * 403 Forbidden
 */
echo \App\Core\View::render('errors/generic', [
    'status' => 403,
    'title' => 'Access denied',
    'message' => 'You do not have permission to view this page. If you believe this is a mistake, sign in with a different account.',
    'debug' => isset($debug) ? $debug : null,
], null);
