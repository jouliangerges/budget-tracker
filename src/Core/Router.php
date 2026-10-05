<?php

declare(strict_types=1);

namespace App\Core;


final class Router
{
    /** @var list<array{method: string, path: string, handler: array{class-string, string}}> */
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

        foreach ($this->routes as $route) {
            if (
                $route['method'] === $request->getMethod()
                && $route['path'] === $request->getPath()
            ) {
                $handler = $route['handler'];
                $className = $handler[0];
                $method = $handler[1];

                $controller = new $className();
                return $controller->$method($request);
            }
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path'   => rtrim($path, '/') ?: '/',
            'handler' => $handler
        ];
    }
}
