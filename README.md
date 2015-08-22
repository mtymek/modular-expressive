Modular Expressive
==================

**Build `zend-expressive` application using Zend Framework-compatible modules.**

[![Build Status](https://travis-ci.org/mtymek/modular-expressive.svg)](https://travis-ci.org/mtymek/modular-expressive)
[![Coverage Status](https://coveralls.io/repos/mtymek/modular-expressive/badge.svg?branch=master&service=github)](https://coveralls.io/github/mtymek/modular-expressive?branch=master)

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

Module class is simplified version of what is consumed by ZF2: 

```php
namespace Application;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
```

Module configuration can be merged to speed up application bootstrap - it is done exactly as in ZF app, 
using system configuration:

```php
return [
    'module_listener_options' => [
        'config_glob_paths' => [
            __DIR__ . '/autoload/{,*.}{global,local}.php'
        ],

        'config_cache_enabled' => true,
        'config_cache_key' => 'config',
        'cache_dir' => 'data/cache',
    ],
];
```

Limitations
-----------

Obviously Expressive is different product than Zend Framework, so you cannot expect every module
to work seamlessly under both environments. ZF architecture is event-driven, so a lot of 3rd party
modules will attach listeners to different points of execution (routing, dispatching, rendering...).
This won't work under Expressive without at least some wiring.

However, there are many useful modules that exist only to provide configuration and factories 
for `ServiceManager`, and they can be re-used with this library without any extra code.
 

Advanced usage
--------------

### Custom ModuleManager

Modular Expressive is designed to keep ModuleManager's overhead minimal, so by default only feature 
it supports is configuration merging. There is a way to enable other module functionalities by
injecting custom ModuleManager into `ModularApplicationFactory`.

For example, here's how to allow modules to execute some initialization logic (using `init()` method):


```php
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\Listener\InitTrigger;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleManager;

$moduleManager = new ModuleManager([]);
$moduleManager->getEventManager()->attach(
    ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, new ModuleResolverListener()
);
$this->moduleManager->getEventManager()->attach(
    ModuleEvent::EVENT_LOAD_MODULE, new InitTrigger()
);

// inject custom manager into application factory
$appFactory = new ModularApplicationFactory($moduleManager);
```

After this setup you can add `init()` method to your modules that will be executed on startup:

```php
namespace Application;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function init()
    {
        // initialization code!
    }
}
```
