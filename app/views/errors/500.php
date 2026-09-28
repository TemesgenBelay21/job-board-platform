<?php
/**
 * 500 Internal Server Error
 */
echo \App\Core\View::render('errors/generic', [
    'status' => 500,
    'title' => 'Something went wrong',
    'message' => 'An unexpected error occurred on our side. Please try again in a moment.',
    'debug' => isset($debug) ? $debug : null,
], null);
