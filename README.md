# Metallike Dependency Injection

Metallike Dependency Injection is a PHP DI Container that allows to easily manager your project dependencies.

This software is part of the Metallike Framework. Nevertheless, the functionalities can also be used without using the framework.

## Install

To install `metallike/metallike-di` simply run the following composer command:

```shell script
composer require metallike/di
``` 

That's it!

## Usage

```php
<?php

use Metallike\Component\DependencyInjection\Container;
use ...

require_once __DIR__ . 'path/to/vendor/autoload.php';

// create a new container
$container = new Container();

// register a service
$container->set('service_id', SomeService::class); 

// retrieve a service
$container->get('service_id');

// register a parameter
$container->setParameter('parameter_id','some value');

// retrieve a parameter
$container->getParameter('parameter_id');
```

To learn more, read the [full documentation](https://link.to/full/doc).

## License

This project uses the following license: [MIT License](https://github.com/metallike/metallike-di/blob/master/LICENSE)