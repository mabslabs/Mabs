<?php

declare(strict_types=1);

use Mabs\Application;
use Mabs\Container\Container;
use Mabs\Router\Router;
use Mabs\Dispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application(debug: true);
    }

    public function testAppStartsInDebugMode(): void
    {
        $this->assertTrue($this->app->isDebugMode());
    }

    public function testContainerIsInstanceOfContainer(): void
    {
        $this->assertInstanceOf(Container::class, $this->app->getContainer());
    }

    public function testApplicationHandlesRequestAndReturnsResponse(): void
    {
        // Mock Router
        $mockRouter = $this->createMock(Router::class);
        $mockResponse = new Response('Hello World', 200);
        $mockRouter->method('handleRequest')->willReturn($mockResponse);

        $this->app->getContainer()['router'] = fn() => $mockRouter;

        $response = $this->app->handleRequest();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello World', $response->getContent());
    }

    public function testEventDispatching(): void
    {
        $mockDispatcher = $this->createMock(EventDispatcher::class);
        $mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('custom_event', ['key' => 'value'])
            ->willReturn('dispatched');

        $this->app->getContainer()['event_dispatcher'] = fn() => $mockDispatcher;

        $result = $this->app->dispatch('custom_event', ['key' => 'value']);
        $this->assertSame('dispatched', $result);
    }

    public function testRouteMounting(): void
    {
        $mockRouter = $this->createMock(Router::class);
        $mockRouter->expects($this->once())
            ->method('mount')
            ->with(
                $this->isInstanceOf(\Mabs\Router\Route::class),
                [Request::METHOD_GET]
            );

        $this->app->getContainer()['router'] = fn() => $mockRouter;
        $this->app->get('/test', fn() => 'ok');
    }
}
