Modular Expressive
==================

**Build `zend-expressive` application using Zend Framework-compatible modules.**

[![Build Status](https://travis-ci.org/mtymek/modular-expressive.svg)](https://travis-ci.org/mtymek/modular-expressive)

There are three main scenarios where Modular Expressive can be very helpful:

* you want to split your application logic into modules that share configuration and services, 
in a same way you would do in ZF2.
* you want to reuse some ZF2 modules (see "[Limitations](#limitations)" section below)
* you are migrating ZF2 application to Expressive 

Installation
------------

Install this library using composer:

```bash
$ composer require mtymek/modular-expressive
```

Usage
-----

`Modular Expressive`'s core is a factory that can load Zend Framework modules, build config for 
ServiceManager, and use it to create `Zend\Expressive\Application` object.  

```php
// index.php
use ModularExpressive\ModularApplicationFactory;

$appFactory = new ModularApplicationFactory();
$app = $appFactory->create(
     // system configuration, similar to ZF2
    [
        'modules' => [
            'Application',
            'AnotherModule'
        ],
        'module_listener_options' => [
            'config_glob_paths' => [
                ['config/autoload/{{,*.}global,{,*.}local}.php'],
            ],
        ]
    ]
);
$app->run();
```


Limitations
-----------

Obviously Expressive is different product than Zend Framework, so you cannot expect every module
to work seamlessly under both environments. ZF architecture is event-driven, so a lot of 3rd party
modules will attach listeners to different points of execution (routing, dispatching, rendering...).
This won't work under Expressive without at least some wiring.

However, there are many useful modules that exist only to provide configuration and factories 
for `ServiceManager`, and they can be re-used with this library without any extra code.
