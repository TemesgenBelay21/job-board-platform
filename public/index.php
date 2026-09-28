<?php
/**
 * Front controller.
 *
 * Every request that is not a real file is rewritten here by .htaccess (Apache)
 * or by the built-in server. It boots the application, dispatches the router and
 * turns any uncaught exception into a rendered error page.
 */



use App\Core\App;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

// The autoloader itself is needed before it can load anything else.
require BASE_PATH . '/app/core/Autoloader.php';
require BASE_PATH . '/app/core/App.php';

App::boot(BASE_PATH);

Session::start();

/**
 * Render an error page for an uncaught exception.
 */
set_exception_handler(function ($exception) {
    $isHttpException = $exception instanceof HttpException;
    $status = $isHttpException ? $exception->getStatusCode() : 500;

    if (!headers_sent()) {
        http_response_code($status);
    }

    // Log the details so failures are diagnosable, but never show internals to
    // visitors unless we are explicitly running in the local environment.
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $exception->getMessage(),
        get_class($exception),
        $exception->getFile(),
        $exception->getLine()
    ));

    $view = $isHttpException && $status < 500 ? 'errors/' . $status : 'errors/500';

    if (!View::exists($view)) {
        $view = 'errors/generic';
    }

    try {
        echo View::render($view, [
            'title' => $isHttpException ? $exception->getMessage() : 'Something went wrong',
            'message' => $exception->getMessage(),
            'debug' => App::isLocal() ? [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ] : null,
        ], 'layouts/minimal');
    } catch (\Throwable $renderFailure) {
        // The error page itself failed, so fall back to plain output. In the
        // local environment the original message is included, otherwise a
        // broken view would hide the actual fault and be impossible to debug.
        error_log(sprintf(
            '[%s] error page failed: %s in %s:%d',
            $exception->getMessage(),
            $renderFailure->getMessage(),
            $renderFailure->getFile(),
            $renderFailure->getLine()
        ));

        $detail = App::isLocal()
            ? '<pre>' . e($exception->getMessage() . "\n\n" . $exception->getTraceAsString()) . '</pre>'
            : '';

        echo '<h1>Error ' . e($status) . '</h1><p>Something went wrong.</p>' . $detail;
    }
});

$router = new Router();

require BASE_PATH . '/app/routes.php';

$router->dispatch();
