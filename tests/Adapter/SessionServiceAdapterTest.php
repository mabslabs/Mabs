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
        print_r($request->getSession());
        $this->container['request'] = $request;
        $this->adapter->boot($this->container);

        $this->adapter->onMabsBoot($this->container);

        $request->expects($this->once())
            ->method('setSession')
            ->with($this->container['session']);

        print_r($request->getSession());
    }

    public function testOnMabsBootSetsSessionInRequest(): void
    {
        // Charge les services de session
        $this->adapter->load($this->container);

        // Crée une fausse requête
        $request = $this->createMock(Request::class);

        // Crée l'événement MABS_ON_BOOT
        $this->container['request'] = $request;
        $this->adapter->onMabsBoot($this->container);

        // Vérifie que la session est bien assignée
        $request->expects($this->once())
            ->method('setSession')
            ->with($this->container['session']);
    }

    public function testBootEventRegistration(): void
    {
        // Test que l'événement est bien enregistré
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
