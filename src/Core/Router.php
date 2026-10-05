<?php

declare(strict_types=1);

namespace App\Core;


final class Router
{
    /** @var list<array{method: string, path: string, handler: array{class-string, string}, pattern: string}> */
    private array $routes = [];


    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->getPath(), $matches)) {

                if ($route['method'] !== $request->getMethod()) {
                    $allowedMethods[] = $route['method'];
                    continue;
                }

                // Keep only named groups: ['id' => '5'] instead of [0 => '/api/...', 'id' => '5', 1 => '5']
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];
                $className = $handler[0];
                $method = $handler[1];

                $controller = new $className();
                return $controller->$method($request, ...$params);
            }
        }

        if ($allowedMethods !== []) {
            return Response::json(['error' => 'Method Not Allowed'], 405)
                ->withHeader('Allow', implode(', ', array_unique($allowedMethods)));
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $path = rtrim($path, '/') ?: '/';

        // '/api/transactions/{id}' → '#^/api/transactions/(?P<id>[^/]+)$#'
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path'   => $path,
            'handler' => $handler,
            'pattern' => $pattern
        ];
    }
}
