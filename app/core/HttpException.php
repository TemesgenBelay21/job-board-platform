<?php

namespace App\Core;

/**
 * Thrown to abort a request and render an error page with a specific status
 * code (404, 403, 419 ...). The global handler in public/index.php catches it.
 */
class HttpException extends \RuntimeException
{
    /** @var int */
    private $statusCode;

    public function __construct($statusCode, $message = '', $previous = null)
    {
        $this->statusCode = (int) $statusCode;

        parent::__construct($message, $this->statusCode, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
