<?php

namespace App\Core;

/**
 * Application container.
 *
 * Holds the configuration array, resolves project paths and works out the base
 * URL the app is being served from, so the same code runs whether it lives in a
 * sub-folder of a web root (http://localhost/job-board-platform/public) or is
 * started through PHP's built-in server (http://127.0.0.1:8080).
 */
class App
{
    /** @var string Absolute path to the project root. */
    private static $basePath;

    /** @var array Configuration values. */
    private static $config = [];

    /** @var bool Whether the app is running in the 'local' environment. */
    private static $local = true;

    public static function boot($basePath)
    {
        self::$basePath = rtrim($basePath, '/\\');
        self::loadConfig();

        date_default_timezone_set(self::config('app.timezone', 'UTC'));

        Autoloader::register(self::$basePath);
        require self::$basePath . '/app/helpers/functions.php';
        require self::$basePath . '/app/helpers/flash.php';

        self::$local = self::config('app.env', 'local') === 'local';

        error_reporting(self::$local ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        ini_set('display_errors', self::$local ? '1' : '0');
    }

    private static function loadConfig()
    {
        $file = self::$basePath . '/config/config.php';

        if (!is_file($file)) {
            // Fall back to the committed example so a fresh clone still boots.
            $file = self::$basePath . '/config/config.example.php';
        }

        $config = require $file;

        self::$config = is_array($config) ? $config : [];
    }

    /**
     * Read a config value using dot notation, e.g. config('database.host').
     */
    public static function config($key = null, $default = null)
    {
        if ($key === null) {
            return self::$config;
        }

        $value = self::$config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function isLocal()
    {
        return self::$local;
    }

    /** Absolute path inside the project, e.g. basePath('/storage/resumes'). */
    public static function basePath($path = '')
    {
        if ($path === '' || $path === null) {
            return self::$basePath;
        }

        return self::$basePath . '/' . ltrim($path, '/');
    }

    /** Absolute path to the public web root. */
    public static function publicPath($path = '')
    {
        return self::basePath('/public' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    /** Absolute path to the private storage folder. */
    public static function storagePath($path = '')
    {
        return self::basePath('/storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Base URL the app is served from, without a trailing slash.
     *
     * An explicit config('app.url') always wins; otherwise it is derived from
     * the location of the front controller so the app is portable.
     */
    public static function baseUrl()
    {
        $configured = self::config('app.url');

        if (!empty($configured)) {
            return rtrim($configured, '/');
        }

        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($directory === '.') {
            $directory = '';
        }

        $https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return $scheme . '://' . $host . $directory;
    }
}
