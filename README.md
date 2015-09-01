# Mabs Framework

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mabslabs/Mabs/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mabslabs/Mabs/?branch=master) [![Code Climate](https://codeclimate.com/github/mabslabs/Mabs/badges/gpa.svg)](https://codeclimate.com/github/mabslabs/Mabs) [![Build Status](https://travis-ci.org/mabslabs/Mabs.svg?branch=master)](https://travis-ci.org/mabslabs/Mabs)

Mabs is a PHP micro framework, speedy, light and easy to learn .

## Features

-	Container
-	Event dispatcher
-	Routing
-	HttpFoundation  (Symfony2 component)
-	An easy way to extend PHP libraries


## Getting Started

### Install

You may install the Mabs Framework with Composer (recommended).

```bash
$ composer require  mabslabs/mabs @dev
```

### Quick start


```php
// web/index.php
<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Mabs\Application();

$app->get('hello/(name)', function ($name) {

    return 'Hello '.$name;
})->run();

```

### More details

```php
// web/index.php
<?php

require_once __DIR__.'/../vendor/autoload.php';
use  \Symfony\Component\HttpFoundation\RedirectResponse;

$app = new Mabs\Application();
$container = $app->getContainer();

$app->get('/', function () use ($container) {
    $url = $container['router']->generateUrl('hello_page', array('name' => 'World'));

    return new RedirectResponse($url);
});

$app->get('hello/(name)', function ($name) {

  return 'Hello '.$name;
}, 'hello_page');

$app->run();

```

## License

  This bundle is available under the [MIT license](LICENSE).
