<?php

use App\Core\Session;
use App\Core\View;

if (!function_exists('csrf_token')) {
    /**
     * The current session's CSRF token.
     */
    function csrf_token()
    {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Hidden input for a form. Every state changing form includes this.
     */
    function csrf_field()
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Hidden input so a browser form can trigger a PUT or DELETE route.
     */
    function method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('active_class')) {
    /**
     * Return $class when the current request path matches $path, otherwise ''.
     * Keeps navigation highlighting in one place.
     */
    function active_class($path, $class = 'is-active')
    {
        $router = new \App\Core\Router();

        return $router->currentPath() === trim($path, '/') ? $class : '';
    }
}

if (!function_exists('app_name')) {
    function app_name()
    {
        return \App\Core\App::config('app.name', 'JobBoard');
    }
}

if (!function_exists('current_user')) {
    /**
     * The signed in user array, or null.
     */
    function current_user()
    {
        return \App\Core\Session::get('user');
    }
}

if (!function_exists('humanize')) {
    /**
     * Turn 'under_review' into 'Under review' for display.
     */
    function humanize($value)
    {
        return ucfirst(str_replace('_', ' ', (string) $value));
    }
}
