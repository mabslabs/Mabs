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

namespace Mabs;

use Mabs\Container\Container;
use Mabs\Dispatcher\EventDispatcher;
use Mabs\Router\Route;
use Mabs\Router\RouteCollection;
use Mabs\Router\Router;
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

    /**
     * check if debug mode is active
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->debug === true;
    }

    /**
     * attach an action for an event
     * @param string $eventName
     * @param callable $callback
     * @param int $priority
     * @return \Mabs\EventDispatcher
     */
    public function on($eventName, \Closure $callback, $priority = 0)
    {
        return $this->container['event_dispatcher']->register($eventName, $callback, $priority);
    }

    /**
     * detach registered actions for an event
     * @param string $eventName
     * @return \Mabs\EventDispatcher
     */
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

    /**
     * check if all component are loaded
     * @return bool
     */
    public function isLoaded()
    {
        return $this->loaded === true;
    }

    /**
     * run applicaton : handle the request and send response
     */
    public function run()
    {
        try {
            if (!$this->isLoaded()) {
                $this->load();
            }

            $response = $this->handleRequest();
            $this->dispatch(Events::MABS_ON_TERMINATE, $response);

        } catch (\Exception $e) {
            $this->dispatch(Events::MABS_HANDLE_EXCEPTION, $e);
            $response = new Response('500 internal server error', 500);
        }

        $response->send();
        $this->dispatch(Events::MABS_ON_FINISH);
    }

    /**
     * handle a Request
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request = null)
    {
        if (!$request) {
            $request = $this->container['request'];
        }

        $this->dispatch(Events::MABS_HANDLE_REQUEST, $request);

        $response = $this->container['router']->handleRequest($request);
        if (! $response instanceof Response) {
            $response = new Response($response, 200);
        }

        return $response;
    }

    /**
     * add GET route
     * @param string $pattern
     * @param Closure|string $callback
     * @param null|string $routeName
     * @return Application
     */
    public function get($pattern, $callback, $routeName = null)
    {
        return $this->mount($pattern, $callback, $routeName, array(Request::METHOD_GET));
    }

    /**
     * add POST route
     * @param string $pattern
     * @param Closure|string $callback
     * @param null|string $routeName
     * @return Application
     */
    public function post($pattern, $callback, $routeName = null)
    {
        return $this->mount($pattern, $callback, $routeName, array(Request::METHOD_POST));
    }

    /**
     * add PUT route
     * @param string $pattern
     * @param Closure|string $callback
     * @param null|string $routeName
     * @return Application
     */
    public function put($pattern, $callback, $routeName = null)
    {
        return $this->mount($pattern, $callback, $routeName, array(Request::METHOD_PUT));
    }

    /**
     * add DELETE route
     * @param string $pattern
     * @param Closure|string $callback
     * @param null|string $routeName
     * @return Application
     */
    public function delete($pattern, $callback, $routeName = null)
    {
        return $this->mount($pattern, $callback, $routeName, array(Request::METHOD_DELETE));
    }

    /**
     * add a route
     * @param string $pattern
     * @param Closure|string $callback
     * @param null|string $routeName
     * @param array HTTP Methode
     * @return Application
     */
    public function mount($pattern, $callback, $routeName = null, $methodes = array())
    {
        $route = new Route();
        $route->setPath($pattern)
            ->setName($routeName)
            ->setCallback($callback);

        $this->container['router']->mount($route, $methodes);

        return $this;
    }

    /**
     * get the DI container
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * lock the conatiner
     */
    public function lock()
    {
        $this->container->lock(true);
        $this->dispatch(Events::MABS_ON_LOCKED);
    }

    /**
     * list of active components
     * @return array
     */
    public function getAdapters()
    {
        return array(
            new \Mabs\Adapter\SessionServiceAdapter(),
        );
    }

    /**
     * load all component in the DI Container
     */
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

    /**
     * initialize all components
     */
    protected function boot()
    {
        foreach ($this->adapters as $adapter) {
            $adapter->boot($this->container);
        }
        $this->dispatch(Events::MABS_ON_BOOT);
    }
}
