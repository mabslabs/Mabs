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

namespace Mabs\Adapter;


use Mabs\Container\Container;
use Mabs\ServiceAdapterInterface;
use Mabs\Events;
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
        $container['event_dispatcher']->register(Events::MABS_ON_BOOT, array($this, 'onMabsBoot'), 128);
    }

    public function onMabsBoot(Container $container)
    {
        $container['request']->setSession($container['session']);
    }
}
