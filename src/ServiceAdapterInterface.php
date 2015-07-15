<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 14/07/15
 * Time: 01:05 ص
 */

namespace Moon;

use Moon\Container\Container;

interface ServiceAdapterInterface
{
    public function load(Container $container);
    public function boot(Container $container);
}
 