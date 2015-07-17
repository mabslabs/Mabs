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
        $container['session.storage.options'] = array();
        $container['session.default_locale'] = 'en';
        $container['session.storage.save_path'] = '/tmp';

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
                $app['session.storage'] = $app['session.storage.native'];
            }
            return new Session($app['session.storage']);
        };
    }

    public function boot(Container $container)
    {
        $container['event_dispatcher']->register(Events::MOON_ON_BOOT, array($this, 'onMoonBoot'), 128);
    }

    public function onMoonBoot(Container $container)
    {
        $container['request']->setSession($container['session']);
    }
}
