<?php

namespace Mabs\Tests;

use Mabs\Application;
use Mabs\Container\Container;
use Mabs\Dispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \PHPUnit\Framework\TestCase;
class ApplicationTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        $this->app = new Application(true); // Activer le mode debug pour les tests
    }

    public function testConstructorSetsDebugMode()
    {
        $app = new Application(true);
        $this->assertTrue($app->isDebugMode());

        $app = new Application(false);
        $this->assertFalse($app->isDebugMode());
    }

    public function testLoadInitializesComponents()
    {
        $this->assertInstanceOf(Container::class, $this->app->getContainer());
    }

    public function testRunHandlesRequestSuccessfully()
    {

        $request = Request::create('/test');

        $response = $this->app->handleRequest($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testGetMethodMountsRoute()
    {
        $this->app->get('example', function () {
            return new Response('Hello, World!');
        });

        $request = Request::create('/example');
        $response = $this->app->handleRequest($request);

        $this->assertEquals('Hello, World!', $response->getContent());
    }

    public function testEventDispatcher()
    {
        $eventCalled = false;

        $this->app->on('test.event', function () use (&$eventCalled) {
            $eventCalled = true;
        });

        $this->app->dispatch('test.event');

        $this->assertTrue($eventCalled);
    }

    public function testDetachRemovesEventListener()
    {
        $eventCalled = false;

        $callback = function () use (&$eventCalled) {
            $eventCalled = true;
        };

        $this->app->on('test.event', $callback);
        $this->app->detach('test.event');

        $this->app->dispatch('test.event');

        $this->assertFalse($eventCalled);
    }
}
