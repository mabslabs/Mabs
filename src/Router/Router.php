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
     * @param \Mabs\Router $router
     */
    public function mount($route, $methodes = array())
    {
        if (empty($methodes)) {
            $this->routeCollection[$route->getName()] = $route;
        } else {
            foreach ($methodes as $methode) {
                $key = $this->getUniqueRouteKey($methode, $route->getName());
                $this->routeCollection[$key] = $route;
            }
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

        foreach ($this->routeCollection as $route) {

            if ($this->match($request, $route)) {

                if (isset($this->routeCollection[$route->getName()])) {
                    return $this->executeController($this->routeCollection[$route->getName()]->getCallback(), $request);
                }

                $key = $this->getUniqueRouteKey($methode, $route->getName());
                if (isset($this->routeCollection[$key])) {
                    return $this->executeController($this->routeCollection[$key]->getCallback(), $request);
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
            $path = str_replace(array('('.$key.')','('.$key.'?)'), $value, $path);
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
        if (isset($this->routeCollection[$routeName])) {
            return $this->routeCollection[$routeName];
        }

        foreach ($this->routeCollection as $route) {
            if ($route->getName() == $routeName) {
                return $route;
            }
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

    private function getUniqueRouteKey($methode, $routeName)
    {
        if (empty($methode)) {
            return $routeName;
        }
        return strtolower($methode) . '::' . $routeName;
    }
}
