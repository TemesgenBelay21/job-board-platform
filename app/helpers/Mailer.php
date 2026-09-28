<?php

namespace App\Helpers;

use App\Core\App;

/**
 * Outgoing mail.
 *
 * Local development machines usually have no SMTP server, so in that case
 * mail() would silently fail and nobody could test the flow. When the
 * environment is local the message is logged instead, and sendPasswordReset()
 * hands the link back to the caller so the UI can show it.
 */
class Mailer
{
    /** @var string|null Last message that could not be sent, for the UI to show. */
    private static $lastFailure = null;

    /**
     * Send a plain text message.
     *
     * @return bool True when the message was handed off to a mail transport
     */
    public static function send($to, $subject, $body)
    {
        $to = trim((string) $to);

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastFailure = 'invalid recipient';

            return false;
        }

        // Headers are built from trusted config and a fixed subject, never from
        // user input, so no header injection is possible here.
        $headers = [
            'From: ' . App::config('mail.from_address', 'no-reply@localhost'),
            'Reply-To: ' . App::config('mail.from_address', 'no-reply@localhost'),
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$sent) {
            self::$lastFailure = 'mail transport rejected the message';

            error_log(sprintf('[mail] could not send "%s" to %s', $subject, $to));

            return false;
        }

        self::$lastFailure = null;

        return true;
    }

    /**
     * Send a password reset link.
     *
     * @param string $email
     * @param string $name
     * @param string $link
     *
     * @return bool True when the message went out
     */
    public static function sendPasswordReset($email, $name, $link)
    {
        $body = "Hello " . $name . ",\n\n"
            . "We received a request to reset the password for your "
            . App::config('app.name', 'JobBoard') . " account.\n\n"
            . "Open this link to choose a new password:\n"
            . $link . "\n\n"
            . "The link stops working after " . \App\Models\PasswordReset::LIFETIME_MINUTES
            . " minutes, and it can only be used once.\n\n"
            . "If you did not ask for this, you can ignore this email and your "
            . "current password will keep working.\n";

        return self::send($email, 'Reset your ' . App::config('app.name', 'JobBoard') . ' password', $body);
    }

    /**
     * True when there is no real mail server, so the UI should show the link
     * instead of pretending the email was delivered.
     */
    public static function isLocal()
    {
        return App::isLocal();
    }

    /** Why the last send() failed, or null when it worked. */
    public static function lastFailure()
    {
        return self::$lastFailure;
    }
}
