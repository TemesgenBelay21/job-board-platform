<?php

namespace App\Core;

/**
 * Renders a view file from app/views and wraps it in a layout.
 *
 * Views are plain PHP files: data is extracted into local variables, the output
 * is buffered and handed to the layout as $content.
 */
class View
{
    /** @var array Shared variables every view receives, e.g. the logged in user. */
    private static $shared = [];

    public static function share($key, $value)
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param string $view   Dot or slash path, e.g. 'jobs/show'
     * @param array  $data   Variables made available to the view
     * @param string $layout Layout inside app/views/layouts, or null for none
     *
     * @return string The rendered HTML
     */
    public static function render($view, array $data = [], $layout = 'main')
    {
        $viewFile = self::resolveView($view);

        if (!is_file($viewFile)) {
            throw new HttpException(500, 'View not found: ' . $view);
        }

        $content = self::capture($viewFile, array_merge(self::$shared, $data));

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::resolveView('layouts/' . $layout);

        if (!is_file($layoutFile)) {
            throw new HttpException(500, 'Layout not found: ' . $layout);
        }

        return self::capture($layoutFile, array_merge(self::$shared, $data, ['content' => $content]));
    }

    /** Check whether a view file exists before trying to render it. */
    public static function exists($view)
    {
        return is_file(self::resolveView($view));
    }

    /** Include a partial from app/views and return its output. */
    public static function partial($name, array $data = [])
    {
        $file = self::resolveView('partials/' . $name);

        if (!is_file($file)) {
            throw new HttpException(500, 'Partial not found: ' . $name);
        }

        return self::capture($file, array_merge(self::$shared, $data));
    }

    private static function capture($file, array $data)
    {
        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $file;
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        return ob_get_clean();
    }

    private static function resolveView($view)
    {
        $view = str_replace(['..', '\\'], '', $view);
        $view = str_replace('.', '/', $view);

        return App::basePath('/app/views/' . ltrim($view, '/') . '.php');
    }
}
