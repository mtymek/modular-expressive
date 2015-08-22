<?php

namespace ModularExpressive;

use InvalidArgumentException;
use Zend\Config\Factory as ConfigFactory;
use Zend\Expressive\Application;
use Zend\Expressive\Container\ApplicationFactory as ExpressiveApplicationFactory;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\Listener\ListenerOptions;
use Zend\ModuleManager\Listener\ModuleResolverListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

class ModularApplicationFactory
{
    /**
     * @var ModuleManagerInterface
     */
    protected $moduleManager;

    /**
     * ModularApplicationFactory constructor.
     * @param ModuleManagerInterface $moduleManager
     */
    public function __construct(ModuleManagerInterface $moduleManager = null)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return ModuleManagerInterface
     */
    protected function getModuleManager()
    {
        if (null == $this->moduleManager) {
            $this->moduleManager = new ModuleManager([]);
            $this->moduleManager->getEventManager()->attach(
                ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, new ModuleResolverListener()
            );
        }

        return $this->moduleManager;
    }

    /**
     * @param $systemConfig
     * @return array application config
     */
    protected function initModules(array $systemConfig)
    {
        $modules = isset($systemConfig['modules']) ? $systemConfig['modules'] : [];

        if (!is_array($modules)) {
            throw new InvalidArgumentException(
                "Unable to process system configuration - 'modules' must be an array."
            );
        }

        if (isset($systemConfig['module_listener_options'])) {
            $listenerOptions = $systemConfig['module_listener_options'];
        } else {
            $listenerOptions = [];
        }

        if (!is_array($listenerOptions)) {
            throw new InvalidArgumentException(
                "Unable to process system configuration - 'module_listener_options' must be an array."
            );
        }

        $moduleManager = $this->getModuleManager();
        $moduleManager->setModules($modules);
        $configListener = new ConfigListener(new ListenerOptions($listenerOptions));
        $moduleManager->getEventManager()->attach($configListener);
        $moduleManager->loadModules();
        $moduleConfig = $configListener->getMergedConfig(false);

        if (!isset($listenerOptions['config_glob_paths'])) {
            return $moduleConfig;
        }

        $paths = $listenerOptions['config_glob_paths'];
        foreach ($paths as $path) {
            $moduleConfig = ArrayUtils::merge($moduleConfig, ConfigFactory::fromFiles(glob($path, GLOB_BRACE)));
        }

        return $moduleConfig;
    }

    /**
     * @param array $applicationConfig
     * @return ServiceManager
     */
    protected function initServiceManager(array $applicationConfig)
    {
        $smConfig = new Config(isset($applicationConfig['service_manager'])?$applicationConfig['service_manager']:[]);
        $serviceManager = new ServiceManager($smConfig);
        $serviceManager->setService('Config', $applicationConfig);
        $serviceManager->setAlias('Configuration', 'Config');
        return $serviceManager;
    }

    /**
     * @param array $systemConfig
     * @return Application
     */
    public function factory(array $systemConfig)
    {
        $applicationConfig = $this->initModules($systemConfig);
        $serviceManager = $this->initServiceManager($applicationConfig);

        $defaultFactory = new ExpressiveApplicationFactory();
        return $defaultFactory($serviceManager);
    }
}
