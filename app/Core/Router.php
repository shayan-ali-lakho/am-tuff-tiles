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

        // Default-deny rules that apply to every route, so a new page cannot forget them:
        // 1) every POST must carry a valid CSRF token
        if ($method === 'POST') {
            // PHP drops the whole body when it is bigger than post_max_size (for example huge photos)
            if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] && $_FILES === []) {
                http_response_code(413);
                echo view('errors/413', ['title' => 'Upload too large']);
                return;
            }

            if (!Csrf::verify($_POST['_token'] ?? null)) {
                http_response_code(419);
                echo view('errors/419', ['title' => 'Session expired']);
                return;
            }
        }

        // 2) everything under /admin needs a logged-in user whose portal_role is admin
        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            Auth::requireAdmin();
        }

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
