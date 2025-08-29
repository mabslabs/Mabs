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

namespace Mabs;

use Closure;
use Mabs\Container\Container;
use Mabs\Dispatcher\EventDispatcher;
use Mabs\Router\Route;
use Mabs\Router\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mabs\Adapter\SessionServiceAdapter;

final class Application
{
    public const VERSION = '3.0.0';

    private readonly Container $container;
    private readonly \SplObjectStorage $adapters;
    private bool $debug;
    private bool $loaded = false;
    private bool $booted = false;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;

        ini_set('display_errors', $debug ? 'on' : 'off');
        error_reporting($debug ? E_ALL : 0);

        $this->adapters = new \SplObjectStorage();
        $this->container = new Container();

        $this->load();
        $this->lock();
    }

    public function isDebugMode(): bool
    {
        return $this->debug;
    }

    public function on(string $eventName, Closure $callback, int $priority = 0): EventDispatcher
    {
        return $this->container['event_dispatcher']->register($eventName, $callback, $priority);
    }

    public function detach(string $eventName): EventDispatcher
    {
        return $this->container['event_dispatcher']->detach($eventName);
    }

    public function dispatch(string $eventName, mixed $data = null): mixed
    {
        return $this->container['event_dispatcher']->dispatch($eventName, $data);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function run(): void
    {
        try {
            if (!$this->isLoaded()) {
                $this->load();
            }

            if (!$this->isBooted()) {
                $this->boot();
            }

            $response = $this->handleRequest();
            $this->dispatch(Events::MABS_ON_TERMINATE, $response);

        } catch (\Throwable $e) {
            $this->dispatch(Events::MABS_HANDLE_EXCEPTION, $e);
            $response = new Response('500 internal server error', 500);
        }

        $response->send();
        $this->dispatch(Events::MABS_ON_FINISH);
    }

    public function handleRequest(?Request $request = null): Response
    {
        $request ??= $this->container['request'];

        $this->dispatch(Events::MABS_HANDLE_REQUEST, $request);

        $response = $this->container['router']->handleRequest($request);
        return $response instanceof Response ? $response : new Response((string)$response, 200);
    }

    public function get(string $pattern, Closure|string $callback, ?string $routeName = null): self
    {
        return $this->mount($pattern, $callback, $routeName, [Request::METHOD_GET]);
    }

    public function post(string $pattern, Closure|string $callback, ?string $routeName = null): self
    {
        return $this->mount($pattern, $callback, $routeName, [Request::METHOD_POST]);
    }

    public function put(string $pattern, Closure|string $callback, ?string $routeName = null): self
    {
        return $this->mount($pattern, $callback, $routeName, [Request::METHOD_PUT]);
    }

    public function delete(string $pattern, Closure|string $callback, ?string $routeName = null): self
    {
        return $this->mount($pattern, $callback, $routeName, [Request::METHOD_DELETE]);
    }

    public function mount(
        string $pattern,
        Closure|string $callback,
        ?string $routeName = null,
        array $methods = []
    ): self {
        $route = (new Route())
            ->setPath($pattern)
            ->setName($routeName)
            ->setCallback($callback);

        $this->container['router']->mount($route, $methods);
        return $this;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function lock(): void
    {
        $this->container->lock(true);
        $this->dispatch(Events::MABS_ON_LOCKED);
    }

    public function getAdapters(): array
    {
        return [new SessionServiceAdapter()];
    }

    protected function load(): void
    {
        $this->container['event_dispatcher'] = fn(Container $container) => new EventDispatcher($container);
        $this->container['request'] = fn() => Request::createFromGlobals();
        $this->container['router'] = fn(Container $container) => new Router();

        foreach ($this->getAdapters() as $adapter) {
            $adapter->load($this->container);
            $this->adapters->attach($adapter);
        }

        $this->loaded = true;
    }

    protected function boot(): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->boot($this->container);
        }

        $this->dispatch(Events::MABS_ON_BOOT);
        $this->booted = true;
    }
}
