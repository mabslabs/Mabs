<?php
/**
 * Mabs framework
 *
 * @author      Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * @copyright   2015 Mohamed Aymen Ben Slimane
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Mohamed Aymen Ben Slimane
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace Mabs\Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Router
{
    private array $routeCollection = [];
    private array $routes = [];

    public const HTTP_METHODS = [
        Request::METHOD_GET,
        Request::METHOD_HEAD,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
        Request::METHOD_PURGE,
        Request::METHOD_OPTIONS,
        Request::METHOD_TRACE,
        Request::METHOD_CONNECT,
    ];

    public function mount(Route $route, array $methods = []): self
    {
        $methods = $methods ?: self::HTTP_METHODS;
        $this->routes[$route->name()] = $route;

        foreach ($methods as $method) {
            $this->routeCollection[$method][$route->name()] = $route;
        }

        return $this;
    }

    public function handle(Request $request): Response
    {
        $method = $request->getMethod();

        if (!isset($this->routeCollection[$method])) {
            return new Response('404 Not Found', 404);
        }

        foreach ($this->routeCollection[$method] as $route) {
            if ($this->match($request, $route)) {
                return $this->execute($route->handler(), $request);
            }
        }

        return new Response('404 Not Found', 404);
    }

    public function generateUrl(string $routeName, array $params = []): string
    {
        $route = $this->getRouteByName($routeName);
        $path = $route->path();

        foreach ($params as $key => $value) {
            $path = str_replace(['{' . $key . '}', '{' . $key . '?}'], $value, $path);
        }

        return $path;
    }

    private function match(Request $request, Route $route): bool
    {
        $currentPath = $this->normalizePath($request->getPathInfo());
        $routePath = $route->path();
        $regex = $route->toRegex();

        if ($currentPath === $routePath) {
            return true;
        }

        if (!empty($regex) && preg_match('#^' . $regex . '/?$#', $currentPath, $matches)) {
            $params = $route->extractParameters($matches);
            $request->query->add($params);
            return true;
        }

        return false;
    }

    private function execute(mixed $handler, Request $request): Response
    {
        $result = is_callable($handler)
            ? call_user_func_array($handler, $request->query->all())
            : $handler;

        return $result instanceof Response
            ? $result
            : new Response((string) $result);
    }

    private function getRouteByName(string $routeName): Route
    {
        return $this->routes[$routeName] ?? throw new \RuntimeException("Route '{$routeName}' not found.");
    }

    private function normalizePath(string $path): string
    {
        $normalized = ltrim($path, '/');
        return str_ends_with($normalized, '/') ? $normalized : $normalized . '/';
    }
}
