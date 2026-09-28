<?php
/**
 * Flash message helpers for views and controllers.
 *
 * Queueing is handled by the session; these functions give controllers a short
 * way to post a message and views a way to read and clear them in one pass.
 */

if (!function_exists('flash')) {
    /**
     * Queue a message for the next request.
     *
     * @param string $type success|error|warning|info
     */
    function flash($type, $message)
    {
        \App\Core\Session::flash($type, $message);
    }
}

if (!function_exists('flash_success')) {
    function flash_success($message)
    {
        flash('success', $message);
    }
}

if (!function_exists('flash_error')) {
    function flash_error($message)
    {
        flash('error', $message);
    }
}

if (!function_exists('flashes')) {
    /**
     * Read and clear every queued message, grouped by type.
     *
     * @return array<string, string[]>
     */
    function flashes()
    {
        return \App\Core\Session::pullFlashes();
    }
}

if (!function_exists('render_flashes')) {
    /**
     * Render the queued messages as alert boxes. Called by the layout.
     */
    function render_flashes()
    {
        $flashes = flashes();

        if (empty($flashes)) {
            return '';
        }

        $html = '';

        foreach ($flashes as $type => $messages) {
            foreach ($messages as $message) {
                $html .= '<div class="alert alert--' . e($type) . '" role="status">'
                    . e($message)
                    . '</div>';
            }
        }

        return $html;
    }
}

if (!function_exists('keep_old_input')) {
    /**
     * Stash the current request body so a failed form can be redisplayed with
     * the values the user already typed. Password fields are never kept.
     */
    function keep_old_input()
    {
        $values = $_POST;
        unset($values['password'], $values['password_confirmation'], $values['_token'], $values['_method']);

        \App\Core\Session::set('_old', $values);
    }
}

if (!function_exists('clear_old_input')) {
    function clear_old_input()
    {
        \App\Core\Session::forget('_old');
    }
}
