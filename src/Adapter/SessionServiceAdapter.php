<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 14/07/15
 * Time: 01:07 ص
 */

namespace Moon\Adapter;


use Moon\Container\Container;
use Moon\ServiceAdapterInterface;
use Moon\Events;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Cookie;

class SessionServiceAdapter implements ServiceAdapterInterface
{
    public function load(Container $container)
    {
        $container['session.test'] = false;
        $container['session.storage.options'] = array();
        $container['session.default_locale'] = 'en';
        $container['session.storage.save_path'] = '/tmp';

        $container['session.storage.test'] = function () {
            return new MockFileSessionStorage();
        };

        $container['session.storage.handler'] = function (Container $c) {
            return new NativeFileSessionHandler($c['session.storage.save_path']);
        };

        $container['session.storage.native'] = function (Container $c) {
            return new NativeSessionStorage(
                $c['session.storage.options'],
                $c['session.storage.handler']
            );
        };

        $container['session'] = function (Container $app) {
            if (!isset($app['session.storage'])) {
                if ($app['session.test']) {
                    $app['session.storage'] = $app['session.storage.test'];
                } else {
                    $app['session.storage'] = $app['session.storage.native'];
                }
            }
            return new Session($app['session.storage']);
        };
    }

    public function boot(Container $container)
    {
        $container['event_dispatcher']->register(Events::MOON_ON_BOOT, array($this, 'onMoonBoot'), 128);

        if ($container['session.test']) {
            $container['event_dispatcher']->register(Events::MOON_HANDLE_REQUEST, array($this, 'onHandleRequest'), 192);
            $container['event_dispatcher']->register(Events::MOON_ON_TERMINATE, array($this, 'onTerminate'), -128);
        }
    }

    public function onMoonBoot(Container $container)
    {
        $container['request']->setSession($container['session']);
    }

    public function onHandleRequest(Container $container)
    {
        // bootstrap the session
        $session = $container['session'];
        $cookies = $container['request']->cookies;
        if ($cookies->has($session->getName())) {
            $session->setId($cookies->get($session->getName()));
        } else {
            $session->migrate(false);
        }
    }

    public function onTerminate(Container $container, $response)
    {
        $session = $container['request']->getSession();
        if ($session && $session->isStarted()) {
            $session->save();
            $params = session_get_cookie_params();
            $response->headers->setCookie(new Cookie($session->getName(), $session->getId(), 0 === $params['lifetime'] ? 0 : time() + $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']));
        }
    }
}
 