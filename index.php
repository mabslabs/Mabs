<?php
/**
 * Created in Moon.
 * User: Mohamed Aymen Ben Slimane <aymen.kernel@gmail.com>
 * Date: 11/07/15
 * Time: 12:27 م
 */

use Moon\Application;

require_once __DIR__.'/vendor/autoload.php';

$app = new Application(true);

$app->run();
