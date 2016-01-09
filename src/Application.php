<?php
namespace Gambit\Mvc;

use Gambit\Mvc\Listener\OnBootstrapListener;
use Zend\Mvc\Application as BaseApplication;
use Zend\ServiceManager\ServiceManager;

class Application extends BaseApplication
{
    /**
     * Static method for quick and easy initialization of the Application.
     *
     * If you use this init() method, you cannot specify a service with the
     * name of 'ApplicationConfig' in your service manager config. This name is
     * reserved to hold the array from application.config.php.
     *
     * The following services can only be overridden from application.config.php:
     *
     * - ModuleManager
     * - SharedEventManager
     * - EventManager & Zend\EventManager\EventManagerInterface
     *
     * All other services are configured after module loading, thus can be
     * overridden by modules.
     *
     * @param array $configuration
     * @return Application
     */
    public static function init($configuration = [])
    {
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : [];
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);
        $serviceManager->get('ModuleManager')->loadModules();

        $listenersFromAppConfig     = isset($configuration['listeners']) ? $configuration['listeners'] : [];
        $config                     = $serviceManager->get('Config');
        $listenersFromConfigService = isset($config['listeners']) ? $config['listeners'] : [];
    
        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));

        $application = $serviceManager->get('Application');
        
        $eventManager = $serviceManager->get('EventManager');
        $bootstrapListener = new OnBootstrapListener();
        $bootstrapListener->attach($application->getEventManager());
        
        return $application->bootstrap($listeners);
    }
}