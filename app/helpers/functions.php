<?php

use App\Core\App;

if (!function_exists('e')) {
    /**
     * Escape a value for safe output in HTML.
     *
     * Every piece of user supplied content printed by a view goes through this.
     */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute URL for an app path, e.g. url('jobs', ['q' => 'php']).
     */
    function url($path = '', array $query = [])
    {
        $url = App::baseUrl() . ($path === '' ? '' : '/' . ltrim($path, '/'));

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}

if (!function_exists('asset')) {
    /**
     * Build the URL of a file inside the public assets folder.
     */
    function asset($path)
    {
        return App::baseUrl() . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Send a redirect header and stop executing.
     */
    function redirect($path = '/', array $query = [])
    {
        header('Location: ' . url($path, $query), true, 302);
        exit;
    }
}

if (!function_exists('redirect_back')) {
    /**
     * Return the user to the page they submitted the form from.
     */
    function redirect_back($fallback = '/')
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        // Only follow our own URLs so the header cannot be used as an open redirect.
        if ($referer !== '' && strpos($referer, App::baseUrl()) === 0) {
            header('Location: ' . $referer, true, 302);
            exit;
        }

        redirect($fallback);
    }
}

if (!function_exists('is_post')) {
    function is_post()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }
}

if (!function_exists('old')) {
    /**
     * Read a value that was submitted with the previous (failed) form request.
     */
    function old($key, $default = '')
    {
        return isset($_SESSION['_old'][$key]) ? $_SESSION['_old'][$key] : $default;
    }
}

if (!function_exists('input')) {
    /**
     * Read a value from the request body.
     */
    function input($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
}

if (!function_exists('query_param')) {
    /**
     * Read a trimmed value from the query string.
     */
    function query_param($key, $default = '')
    {
        if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
            return $default;
        }

        return trim($_GET[$key]);
    }
}
