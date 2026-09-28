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

if (!function_exists('is_own_url')) {
    /**
     * True when a URL points back at this installation.
     *
     * Host, port and path prefix are compared separately rather than with a
     * string prefix test: with base http://localhost, a plain prefix check would
     * also accept http://localhost.example.com.
     *
     * @param string $candidate An absolute URL, e.g. from the Referer header
     */
    function is_own_url($candidate)
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        $parts = parse_url($candidate);
        $base = parse_url(App::baseUrl());

        // A relative URL can only refer to this host, but it is never accepted
        // here: callers only ever pass absolute URLs.
        if ($parts === false || empty($parts['host'])) {
            return false;
        }

        if (empty($base['host'])) {
            return false;
        }

        if (strcasecmp($parts['host'], $base['host']) !== 0) {
            return false;
        }

        // Ports matter: :8080 is a different origin to the default.
        $candidatePort = isset($parts['port']) ? (int) $parts['port'] : default_port($parts['scheme']);
        $basePort = isset($base['port']) ? (int) $base['port'] : default_port($base['scheme']);

        if ($candidatePort !== $basePort) {
            return false;
        }

        $basePath = isset($base['path']) ? rtrim($base['path'], '/') : '';
        $candidatePath = isset($parts['path']) ? $parts['path'] : '';

        return $basePath === '' || strpos($candidatePath, $basePath) === 0;
    }
}

if (!function_exists('default_port')) {
    /** The port a scheme uses when none is written out. */
    function default_port($scheme)
    {
        return strcasecmp((string) $scheme, 'https') === 0 ? 443 : 80;
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
        if (is_own_url($referer)) {
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
