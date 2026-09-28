<?php
/**
 * Example configuration.
 *
 * Copy this file to config/config.php and fill in your own credentials.
 * config/config.php is ignored by git, so your real values stay private.
 *
 *   cp config/config.php config.example.php   # (or just copy in an editor)
 */

return [
    'app' => [
        // 'local' shows the password reset link on screen and renders detailed
        // error pages. Use 'production' when the app is publicly reachable.
        'env' => 'local',

        // Left null, the base URL is detected automatically from the request, which
        // lets the app run from any sub-folder. Set it explicitly if you ever
        // generate absolute URLs outside of a web request (queue workers, mailers).
        'url' => null,

        // Shown in the header and page titles.
        'name' => 'JobBoard',

        // Seconds of inactivity before a session is expired and the user is logged out.
        'session_timeout' => 1800,
    ],

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'job_board_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'uploads' => [
        'resume_max_size' => 2 * 1024 * 1024,   // 2 MB
        'resume_extensions' => ['pdf', 'docx'],
        'logo_max_size' => 1024 * 1024,         // 1 MB
        'logo_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'password' => [
        // Minutes a forgot-password reset link stays valid.
        'reset_token_ttl' => 60,
    ],

    'pagination' => [
        'per_page' => 9,
    ],
];
