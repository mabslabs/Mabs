# Mabs Framework

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mabslabs/Mabs/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mabslabs/Mabs/?branch=master)

Mabs is a PHP micro framework, speedy, light and easy to learn .

## Getting Started

### Install

You may install the Mabs Framework with Composer (recommended).

```bash
$ composer require 'mabslabs/mabs'
```

### Rapid start


```php
// web/index.php
<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Mabs\Application();

$app->get('hello/(name)', function ($name) use ($app) {

  return 'Hello '.$name;

}, 'hello_page');
```

## License

  This bundle is available under the [MIT license](LICENSE).
