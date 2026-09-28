<?php

namespace App\Core;

/**
 * Session wrapper.
 *
 * Handles secure cookie settings, an inactivity timeout, id regeneration on
 * login and the short lived flash message queue.
 */
class Session
{
    /** Session key holding the timestamp of the last request. */
    const LAST_ACTIVITY = 'last_activity';

    /** Session key the queued flash messages live under. */
    const FLASH_KEY = '_flash';

    public static function start()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = self::isHttps();

        session_name(App::config('app.session_name', 'jobboard_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => self::cookiePath(),
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (self::hasExpired()) {
            self::destroy();
            session_start();
            self::flash('warning', 'Your session expired after a period of inactivity. Please sign in again.');
        }

        $_SESSION[self::LAST_ACTIVITY] = time();
    }

    /** Absolute cookie path scoped to the folder the app lives in. */
    private static function cookiePath()
    {
        $path = parse_url(App::baseUrl(), PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        return rtrim($path, '/') . '/';
    }

    private static function isHttps()
    {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    }

    private static function hasExpired()
    {
        $timeout = (int) App::config('app.session_timeout', 1800);

        if ($timeout <= 0 || !isset($_SESSION[self::LAST_ACTIVITY])) {
            return false;
        }

        return (int) $_SESSION[self::LAST_ACTIVITY] + $timeout < time();
    }

    public static function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function forget($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Queue a message for the next request.
     *
     * @param string $type    success|error|warning|info
     * @param string $message
     */
    public static function flash($type, $message)
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }

        if (!isset($_SESSION[self::FLASH_KEY][$type])) {
            $_SESSION[self::FLASH_KEY][$type] = [];
        }

        $_SESSION[self::FLASH_KEY][$type][] = $message;
    }

    /**
     * Return every queued flash message and empty the queue.
     */
    public static function pullFlashes()
    {
        $flashes = isset($_SESSION[self::FLASH_KEY]) ? $_SESSION[self::FLASH_KEY] : [];

        unset($_SESSION[self::FLASH_KEY]);

        return is_array($flashes) ? $flashes : [];
    }

    /** Keep the current data but issue a brand new session id. */
    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
