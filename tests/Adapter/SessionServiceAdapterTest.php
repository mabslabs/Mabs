<?php
namespace Mabs\Tests\Adapter;

use Mabs\Adapter\SessionServiceAdapter;
use Mabs\Container\Container;
use Mabs\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionServiceAdapterTest extends TestCase
{
    private Container $container;
    private SessionServiceAdapter $adapter;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->adapter   = new SessionServiceAdapter();

        $eventDispatcherMock                 = $this->createMock(\Mabs\Dispatcher\EventDispatcherInterface::class);
        $this->container['event_dispatcher'] = $eventDispatcherMock;
    }

    public function testLoadInitializesSessionServices(): void
    {
        $this->adapter->load($this->container);

        $this->assertTrue(isset($this->container['session.storage.handler']));
        $this->assertTrue(isset($this->container['session.storage.native']));
        $this->assertTrue(isset($this->container['session']));
    }

    public function testSessionHandlerIsNativeFileSessionHandler(): void
    {
        $this->adapter->load($this->container);

        $handler = $this->container['session.storage.handler'];
        $this->assertInstanceOf(NativeFileSessionHandler::class, $handler);
    }

    public function testSessionStorageIsNativeSessionStorage(): void
    {
        $this->adapter->load($this->container);

        $storage = $this->container['session.storage.native'];
        $this->assertInstanceOf(NativeSessionStorage::class, $storage);
    }

    public function testSessionIsCreated(): void
    {
        $this->adapter->load($this->container);

        $session = $this->container['session'];
        $this->assertInstanceOf(Session::class, $session);
    }

    public function testBootSetsSessionInRequest(): void
    {
        $this->adapter->load($this->container);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('setSession')
            ->with($this->container['session']);

        $this->container['request'] = $request;
        $this->adapter->boot($this->container);

        $this->adapter->onMabsBoot($this->container);
    }

    public function testOnMabsBootSetsSessionInRequest(): void
    {
        $this->adapter->load($this->container);

        $request = $this->createMock(Request::class);

        $request->expects($this->once())
            ->method('setSession')
            ->with($this->container['session']);

        $this->container['request'] = $request;
        $this->adapter->onMabsBoot($this->container);
    }

    public function testBootEventRegistration(): void
    {
        $this->container['event_dispatcher']->expects($this->once())
            ->method('register')
            ->with(
                Events::MABS_ON_BOOT,
                [$this->adapter, 'onMabsBoot'],
                128
            );

        $this->adapter->boot($this->container);
    }
}
