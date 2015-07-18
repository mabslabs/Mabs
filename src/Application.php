<?php
/**
 * Moon framework
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

namespace Moon;

use Moon\Container\Container;
use Moon\Dispatcher\EventDispatcher;
use Moon\Router\Route;
use Moon\Router\RouteCollection;
use Moon\Router\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application
{
    const VERSION = '1.0.0-dev';

    protected $container;

    protected $adapters;

    protected $debug;

    protected $loaded = false;

    public function __construct($debug = false)
    {
        if ($debug) {
            ini_set('display_errors', 'on');
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
        $this->debug = $debug;
        $this->adapters = new \SplObjectStorage();
        $this->container = new Container();

        $this->load();

        $this->lock();

        $this->boot();
    }

    public function on($eventName, \Closure $callback, $priority = 0)
    {
        return $this->container['event_dispatcher']->register($eventName, $callback, $priority);
    }

    public function detach($eventName)
    {
        return $this->container['event_dispatcher']->detach($eventName);
    }

    /**
     * @param string $eventName
     * @param mixed $data
     * @return mixed
     */
    public function dispatch($eventName, $data = null)
    {
        return $this->container['event_dispatcher']->dispatch($eventName, $data);
    }

    public function isLoaded()
    {
        return $this->loaded === true;
    }


    public function run()
    {
        try {
            if (!$this->isLoaded()) {
                $this->load();
            }

            $response = $this->handleRequest();
            $this->dispatch(Events::MOON_ON_TERMINATE, $response);

        } catch (\Exception $e) {
            $this->dispatch(Events::MOON_HANDLE_EXCEPTION, $e);
            $response = new Response('500 internal server error', 500);
        }

        $response->send();
        $this->dispatch(Events::MOON_ON_FINISH);
    }

    public function handleRequest(Request $request = null)
    {
        if (!$request) {
            $request = $this->container['request'];
        }

        $this->dispatch(Events::MOON_HANDLE_REQUEST, $request);

        $response = $this->container['router']->handleRequest($request);
        if (! $response instanceof Response) {
            $response = new Response('500 internal server error', 500);
        }

        return $response;
    }

    public function get($pattern, $callback, $routeName = null)
    {
        $this->mount($pattern, $callback, $routeName, array(Request::METHOD_GET));
    }

    public function post($pattern, $callback, $routeName = null)
    {
        $this->mount($pattern, $callback, $routeName, array(Request::METHOD_POST));
    }

    public function put($pattern, $callback, $routeName = null)
    {
        $this->mount($pattern, $callback, $routeName, array(Request::METHOD_PUT));
    }

    public function delete($pattern, $callback, $routeName = null)
    {
        $this->mount($pattern, $callback, $routeName, array(Request::METHOD_DELETE));
    }

    public function mount($pattern, $callback, $routeName = null, $methodes = array())
    {
        $route = new Route();
        $route->setPath($pattern)
            ->setName($routeName)
            ->setCallback($callback);

        $this->container['router']->mount($route, $methodes);
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function lock()
    {
        $this->container->lock(true);
        $this->dispatch(Events::MOON_ON_LOCKED);
    }

    public function getAdapters()
    {
        return array(
            new \Moon\Adapter\SessionServiceAdapter(),
        );
    }

    protected function load()
    {
        $this->container['event_dispatcher'] = function (Container $container) {
            return new EventDispatcher($container);
        };

        $this->container['request'] = function () {
            return Request::createFromGlobals();
        };

        $this->container['router'] = function (Container $container) {
            return new Router();
        };

        foreach ($this->getAdapters() as $adapter) {
            $adapter->load($this->container);
            $this->adapters->attach($adapter);
        }

        $this->loaded = true;
    }

    protected function boot()
    {
        foreach ($this->adapters as $adapter) {
            $adapter->boot($this->container);
        }
        $this->dispatch(Events::MOON_ON_BOOT);
    }
}
