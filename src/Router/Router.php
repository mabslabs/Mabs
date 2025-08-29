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

namespace Mabs\Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    protected $routeCollection = array();

    protected $routes = array();

    public static $httpMethodes = array(
        Request::METHOD_POST,
        Request::METHOD_HEAD,
        Request::METHOD_GET,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
        Request::METHOD_PURGE,
        Request::METHOD_OPTIONS,
        Request::METHOD_TRACE,
        Request::METHOD_CONNECT,
    );

    /**
     * mount controller for given route
     * @param $route
     * @param array $methodes
     * @return \Mabs\Router
     */
    public function mount($route, $methodes = array())
    {
        if (empty($methodes)) {
            $methodes = self::$httpMethodes;
        }
        $this->routes[$route->getName()] = $route;
        foreach ($methodes as $methode) {
            if (!isset($this->routeCollection[$methode])) {
                $this->routeCollection[$methode] = array();
            }
            $this->routeCollection[$methode][$route->getName()] = $route;
        }

        return $this;
    }

    /**
     * handle Request and get the response
     * @param Request $request
     * @return mixed|Response
     */
    public function handleRequest(Request $request)
    {
        $methode = $request->getMethod();

        if (!isset($this->routeCollection[$methode])) {
            return new Response('404 Not Found', 404);
        }
        $routes = $this->routeCollection[$methode];
        foreach ($routes as $route) {

            if ($this->match($request, $route)) {

                if (isset($routes[$route->getName()])) {
                    return $this->executeController($routes[$route->getName()]->getCallback(), $request);
                }
            }
        }

        return new Response('404 Not Found', 404);
    }

    /**
     * generate Url for the given route name
     * @param $routeName
     * @param array $params
     * @return mixed
     */
    public function generateUrl($routeName, $params = array())
    {
        $route = $this->getRouteByName($routeName);
        $path = $route->getPath();

        foreach ($params as $key => $value) {
            $path = str_replace(array('(' . $key . ')', '(' . $key . '?)'), $value, $path);
        }

        return $path;
    }

    protected function executeController($controller, Request $request)
    {
        return call_user_func_array($controller, $request->query->all());
    }

    protected function match(Request $request, Route $route)
    {
        $currentPath = $this->getCurrentPath($request);
        $routePath = $route->getPath();

        $regex = $route->getRegularExpression();

        if ($currentPath == $routePath) {

            return true;
        } else if (!empty($regex) && preg_match('#^' . $regex . '\/?$#', $currentPath, $matches)) {
            $request->query->add($route->getNamesParameters($matches));

            return true;
        }

        return false;
    }

    private function getRouteByName($routeName)
    {
        if (isset($this->routes[$routeName])) {
            return $this->routes[$routeName];
        }

        throw new \RuntimeException('route ' . $routeName . ' not found');
    }

    private function getCurrentPath(Request $request)
    {
        $currentPath = ltrim($request->getPathInfo(), '/');
        if (empty($currentPath) || $currentPath[strlen($currentPath) - 1] != '/') {
            $currentPath .= '/';
        }

        return $currentPath;
    }
}
