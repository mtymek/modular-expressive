<?php

namespace ModularExpressive\Test;

use InvalidArgumentException;
use ModularExpressive\ModularApplicationFactory;
use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use stdClass;
use Zend\Expressive\Application;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

class ModularApplicationFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryCreatesServiceApplication()
    {
        $factory = new ModularApplicationFactory();
        $application = $factory->factory([]);
        $this->assertInstanceOf(Application::class, $application);
    }

    public function provideInvalidConfig()
    {
        return [
            'invalid_modules_1' => [['modules' => '']],  // string given, should be array
            'invalid_modules_2' => [['modules' => new stdClass()]],  // object given, should be array
            'invalid_paths' => [
                [
                    'module_listener_options' => [
                        'config_glob_paths' => '/some/path' // string given, should be an array
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidConfig
     */
    public function testFactoryThrowsExceptionForInvalidConfig($config)
    {
        $factory = new ModularApplicationFactory();
        $this->setExpectedException(InvalidArgumentException::class);
        $factory->factory($config);
    }

    public function testFactoryMergesConfiguration()
    {
        $factory = new ModularApplicationFactory();
        $application = $factory->factory(
            [
                'module_listener_options' => [
                    'config_glob_paths' => [__DIR__ . '/TestAsset/config/*.php']
                ],
            ]
        );

        $config = $application->getContainer()->get('Config');
        $this->assertInternalType('array', $config);
        $this->assertEquals('val1', $config['key1']);
        $this->assertEquals('val2', $config['key2']);
        $this->assertEquals(2, $config['merged']);
    }

    public function testFactoryBootstrapsModules()
    {
        $moduleManager = new ModuleManager([]);
        $moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, function(ModuleEvent $event) {
            $this->assertEquals('Foo', $event->getModuleName());
            return new StdClass();
        });

        $factory = new ModularApplicationFactory($moduleManager);
        $factory->factory(['modules' => ['Foo']]);
    }
}
