<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 10/07/15
 * Time: 06:17 م
 */

namespace Moon;

use Moon\Container\Container;
use Moon\Dispatcher\EventDispatcher;
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
            $response->send();
            $this->dispatch(Events::MOON_ON_FINISH);

        } catch (\Exception $e) {
            $this->dispatch(Events::MOON_HANDLE_EXCEPTION, $e);
            var_dump($e);
        }
    }

    public function handleRequest(Request $request = null)
    {
        if (!$request) {
            $request = $this->container['request'];
        }
        $this->dispatch(Events::MOON_HANDLE_REQUEST, $request);


        $response = new Response('Hello', 200, array('Content-Type' => 'text/html'));;

        return $response;
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

    protected function load()
    {
        $this->container['event_dispatcher'] = function (Container $c) {
            return new EventDispatcher($c);
        };

        $this->container['request'] = function () {
            return Request::createFromGlobals();
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

    public function getAdapters()
    {
        return array(
            new \Moon\Adapter\SessionServiceAdapter(),
        );
    }
}
