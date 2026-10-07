<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny router: exact paths plus {param} segments, e.g. /product/{slug}.
 * Handlers are [ControllerClass::class, 'method'] or a callable.
 */
final class Router
{
    /** @var array<string, list<array{0: string, 1: array|callable}>> */
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array|callable $handler): void
    {
        $path = rtrim($path, '/') ?: '/';
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = ['#^' . $pattern . '$#', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);

        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));
        $path = '/' . trim($path, '/');

        foreach ($this->routes[$method] ?? [] as [$regex, $handler]) {
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->invoke($handler, $params);
                return;
            }
        }

        http_response_code(404);
        echo view('errors/404', ['title' => 'Page not found']);
    }

    private function invoke(array|callable $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $handler = [new $class(), $action];
        }

        call_user_func_array($handler, $params);
    }
}
