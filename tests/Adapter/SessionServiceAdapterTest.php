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
        // Initialisation du container et de l'adaptateur
        $this->container = new Container();
        $this->adapter   = new SessionServiceAdapter();

        // Mock d'un EventDispatcher
        $eventDispatcherMock                 = $this->createMock(\Mabs\Dispatcher\EventDispatcherInterface::class);
        $this->container['event_dispatcher'] = $eventDispatcherMock;
    }

    public function testLoadInitializesSessionServices(): void
    {
        // Charge les services de session dans le container
        $this->adapter->load($this->container);

        // Vérifie que les services de session existent dans le container
        $this->assertTrue(isset($this->container['session.storage.handler']));
        $this->assertTrue(isset($this->container['session.storage.native']));
        $this->assertTrue(isset($this->container['session']));
    }

    public function testSessionHandlerIsNativeFileSessionHandler(): void
    {
        // Charge les services de session
        $this->adapter->load($this->container);

        // Vérifie que le handler de session est bien de type NativeFileSessionHandler
        $handler = $this->container['session.storage.handler'];
        $this->assertInstanceOf(NativeFileSessionHandler::class, $handler);
    }

    public function testSessionStorageIsNativeSessionStorage(): void
    {
        // Charge les services de session
        $this->adapter->load($this->container);

        // Vérifie que le stockage de session est bien de type NativeSessionStorage
        $storage = $this->container['session.storage.native'];
        $this->assertInstanceOf(NativeSessionStorage::class, $storage);
    }

    public function testSessionIsCreated(): void
    {
        // Charge les services de session
        $this->adapter->load($this->container);

        // Vérifie que la session est bien créée
        $session = $this->container['session'];
        $this->assertInstanceOf(Session::class, $session);
    }

    public function testBootSetsSessionInRequest(): void
    {
        // Charge les services de session
        $this->adapter->load($this->container);

        // Crée une fausse requête
        $request = $this->createMock(Request::class);

        // Attache la session à la requête via le boot
        $this->container['request'] = $request;
        $this->adapter->boot($this->container);

        // Vérifie que la session est bien assignée à la requête
        $request->expects($this->once())
            ->method('setSession')
            ->with($this->container['session']);
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
