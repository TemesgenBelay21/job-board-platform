<?php

namespace App\Core;

/**
 * CSRF protection.
 *
 * A single random token per session is stored in the session and embedded in
 * every state changing form. Because the session cookie is SameSite=Lax, a
 * cross-site POST cannot both carry the cookie and supply the matching token.
 */
class Csrf
{
    const SESSION_KEY = '_csrf_token';

    /** The current session's token, generating one on first use. */
    public static function token()
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Issue a new token. Called on login and logout so a token can never be
     * reused across privilege changes.
     */
    public static function regenerate()
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);

        return $token;
    }

    /**
     * Constant time comparison of the submitted token against the session one.
     */
    public static function isValid($submitted)
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '' || !is_string($submitted) || $submitted === '') {
            return false;
        }

        return hash_equals($token, $submitted);
    }

    /**
     * Abort the request unless the request carries a valid token.
     *
     * @throws HttpException 419 when the token is missing or does not match
     */
    public static function verify()
    {
        $submitted = isset($_POST['_token']) ? $_POST['_token'] : null;

        if (!self::isValid($submitted)) {
            throw new HttpException(
                419,
                'Your session token expired or was invalid. Please go back, reload the page and try again.'
            );
        }
    }
}
