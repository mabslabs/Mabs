<?php

require_once __DIR__.'/vendor/autoload.php';

$app = new Mabs\Application(true);

$app->get('hello/{name}', function ($name) {

    return 'Hello '.$name;
})->run();