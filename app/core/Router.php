<?php

namespace App\Core;

/**
 * Routes an incoming request to a controller action.
 *
 * Patterns support named parameters in {braces} and the request is matched
 * against the path only, so query string filters never affect routing.
 */
class Router
{
    /** @var array<string, array<string, array>> method => path => definition */
    private $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * Register a GET route.
     *
     * @param string $pattern e.g. 'jobs/{id:\d+}'
     * @param array  $handler [Controller::class, 'method']
     */
    public function get($pattern, array $handler)
    {
        return $this->add('GET', $pattern, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post($pattern, array $handler)
    {
        return $this->add('POST', $pattern, $handler);
    }

    private function add($method, $pattern, array $handler)
    {
        $this->routes[$method][$this->normalize($pattern)] = [
            'handler' => $handler,
            'regex' => $this->toRegex($this->normalize($pattern)),
        ];

        return $this;
    }

    /** Strip leading and trailing slashes so '/jobs' and 'jobs' are the same route. */
    private function normalize($pattern)
    {
        return trim($pattern, '/');
    }

    /**
     * Turn a route pattern into a PCRE pattern.
     *
     * {id}            matches a slug: letters, digits, dash and underscore
     * {id:\d+}        matches using the given regular expression
     */
    private function toRegex($path)
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            function ($matches) {
                $name = $matches[1];
                $expression = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : '[A-Za-z0-9_-]+';

                return '(?P<' . $name . '>' . $expression . ')';
            },
            $path
        );

        return '#^' . $regex . '$#';
    }

    /**
     * The current request path, with the folder the app is served from and the
     * query string removed. Always returned without a leading slash.
     */
    public function currentPath()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }

        $basePath = parse_url(App::baseUrl(), PHP_URL_PATH);
        if (is_string($basePath) && $basePath !== '' && $basePath !== '/') {
            $basePath = rtrim($basePath, '/');
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            }
        }

        return $this->normalize($uri);
    }

    public function currentMethod()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        // Browsers send GET after a successful POST when a controller redirects.
        return $method === 'POST' && isset($_POST['_method'])
            ? strtoupper($_POST['_method'])
            : $method;
    }

    /**
     * Find and run the action for the current request.
     *
     * @throws HttpException 405 when the path exists for another method, 404 otherwise
     */
    public function dispatch()
    {
        $path = $this->currentPath();
        $method = $this->currentMethod();

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $route) {
                if (preg_match($route['regex'], $path, $matches)) {
                    return $this->call($route['handler'], $this->parameters($matches));
                }
            }
        }

        $allowed = [];

        foreach ($this->routes as $routeMethod => $routes) {
            if ($routeMethod === $method) {
                continue;
            }

            foreach ($routes as $route) {
                if (preg_match($route['regex'], $path)) {
                    $allowed[] = $routeMethod;
                    break;
                }
            }
        }

        if (!empty($allowed)) {
            header('Allow: ' . implode(', ', $allowed));
            throw new HttpException(405, 'Method not allowed for this URL.');
        }

        throw new HttpException(404, 'The page you requested could not be found.');
    }

    /** Keep only the named parameters captured by the pattern. */
    private function parameters(array $matches)
    {
        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[] = $value;
            }
        }

        return $parameters;
    }

    private function call(array $handler, array $parameters)
    {
        list($class, $action) = $handler;

        if (!class_exists($class) || !method_exists($class, $action)) {
            throw new HttpException(500, 'Route handler ' . $class . '::' . $action . ' does not exist.');
        }

        $controller = new $class();
        $result = call_user_func_array([$controller, $action], $parameters);

        // Actions may either render themselves or hand back a ready string
        // (used by the file download endpoints).
        if (is_string($result)) {
            echo $result;
        }

        return $result;
    }
}
