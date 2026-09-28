<?php

namespace App\Core;

/**
 * Minimal PSR-4 style autoloader for the App namespace.
 *
 * Keeps the project framework-free while still allowing a clean
 * `App\Models\Job` style reference to resolve to `app/models/Job.php`.
 */
class Autoloader
{
    /** Namespace prefix => directory relative to the project base path. */
    private static $prefixes = [
        'App\\Core\\' => '/app/core/',
        'App\\Controllers\\' => '/app/controllers/',
        'App\\Models\\' => '/app/models/',
        'App\\Helpers\\' => '/app/helpers/',
    ];

    public static function register($basePath)
    {
        $basePath = rtrim($basePath, '/\\');

        spl_autoload_register(function ($class) use ($basePath) {
            $file = self::resolve($class, $basePath);

            if ($file !== null) {
                require $file;
            }
        });
    }

    private static function resolve($class, $basePath)
    {
        foreach (self::$prefixes as $prefix => $directory) {
            if (strpos($class, $prefix) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $basePath . $directory . str_replace('\\', '/', $relative) . '.php';

            return is_file($file) ? $file : null;
        }

        return null;
    }
}
