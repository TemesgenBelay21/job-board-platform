<?php

namespace App\Core;

/**
 * Base controller.
 *
 * Holds the shared plumbing every action needs: view rendering, redirects,
 * CSRF verification, the signed in user, and the role guards.
 *
 * Guards live here rather than in the route table on purpose. Routes only say
 * which controller handles a URL, so the check cannot be bypassed by guessing
 * a path or a role - requireRole() is called by the action itself, or by
 * requireLogin() in a controller constructor.
 */
abstract class Controller
{
    /** @var array|null The signed in user, lazily loaded from the session */
    private $user;

    /**
     * Render a view inside a layout.
     *
     * @param string $view   e.g. 'jobs/show'
     * @param array  $data   variables for the view
     * @param string $layout layout name, or null for no layout
     */
    protected function view($view, array $data = [], $layout = 'main')
    {
        $data = $this->withSharedData($data);

        return View::render($view, $data, $layout);
    }

    /** Render a view without a layout (for partial style responses). */
    protected function viewPartial($view, array $data = [])
    {
        $data = $this->withSharedData($data);

        return View::render($view, $data, null);
    }

    /**
     * Add the variables every view needs: the signed in user and the unread
     * notification count for the header bell. Values passed in by the action
     * always win, so a view can override them.
     */
    private function withSharedData(array $data)
    {
        if (!array_key_exists('user', $data)) {
            $data['user'] = $this->user();
        }

        if (!array_key_exists('unreadCount', $data)) {
            $data['unreadCount'] = $this->unreadNotificationCount();
        }

        return $data;
    }

    /** Redirect and stop. */
    protected function redirect($path, array $query = [])
    {
        redirect($path, $query);
    }

    /**
     * Handle a rejected form: stash the input, flash the first error and go
     * back to the form.
     */
    protected function backWithErrors(array $errors, $fallback = '/')
    {
        keep_old_input();

        foreach ($errors as $message) {
            flash_error($message);
        }

        redirect_back($fallback);
    }

    /**
     * Block the request unless a user is signed in.
     *
     * @throws HttpException 403
     */
    protected function requireLogin()
    {
        if ($this->user() === null) {
            // Remember where they were heading so login can send them back.
            Session::set('intended_url', $this->currentUrl());

            Session::flash('warning', 'Please sign in to continue.');
            throw new HttpException(403, 'You must be signed in to view that page.');
        }
    }

    /**
     * Block the request unless the signed in user has one of the given roles.
     *
     * Called at the top of every protected action, so a seeker who types
     * /employer/jobs gets a 403 rather than another role's data.
     *
     * @param string|array $roles
     *
     * @throws HttpException 403
     */
    protected function requireRole($roles)
    {
        $this->requireLogin();

        $roles = (array) $roles;
        $user = $this->user();

        if (!in_array($user['role'], $roles, true)) {
            throw new HttpException(403, 'Your account does not have access to that page.');
        }
    }

    /** The signed in user array, or null. */
    protected function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $user = Session::get('user');

        if (!is_array($user) || !isset($user['id'])) {
            return null;
        }

        // A deactivated or deleted account must not keep browsing on an old
        // session, so the stored user is re-checked against the database.
        $fresh = (new \App\Models\User())->find($user['id']);

        if ($fresh === null || $fresh['status'] !== 'active') {
            $this->signOut();

            return null;
        }

        $this->user = [
            'id' => (int) $fresh['id'],
            'name' => $fresh['name'],
            'email' => $fresh['email'],
            'role' => $fresh['role'],
        ];

        return $this->user;
    }

    protected function userId()
    {
        $user = $this->user();

        return $user === null ? null : (int) $user['id'];
    }

    protected function isLoggedIn()
    {
        return $this->user() !== null;
    }

    protected function hasRole($role)
    {
        $user = $this->user();

        return $user !== null && $user['role'] === $role;
    }

    /** Sign a user in: fresh session id, new CSRF token, user in session. */
    protected function authenticate(array $user)
    {
        Session::regenerate();
        Csrf::regenerate();

        Session::set('user', [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);
    }

    /** Sign a user out and destroy the session completely. */
    protected function signOut()
    {
        Session::destroy();

        // A new session is started so the "you have signed out" flash has
        // somewhere to live.
        Session::start();
        Session::flash('success', 'You have been signed out.');
    }

    /**
     * Verify the CSRF token on a state changing request.
     *
     * @throws HttpException 419
     */
    protected function verifyCsrf()
    {
        Csrf::verify();
    }

    protected function unreadNotificationCount()
    {
        $user = $this->user();

        if ($user === null) {
            return 0;
        }

        return (new \App\Models\Notification())->unreadCount($user['id']);
    }

    /** Full URL of the current request, used for the post-login redirect. */
    protected function currentUrl()
    {
        return App::baseUrl() . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
    }

    /** The page that requires the current request method, or 404. */
    protected function requireMethod($method)
    {
        $current = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        if ($current !== strtoupper($method)) {
            throw new HttpException(405, 'That type of request is not supported for this page.');
        }
    }
}
