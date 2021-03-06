<?php
namespace Gambit\Mvc\Service;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Stdlib\ArrayUtils;

class ServiceManagerConfig extends Config
{
    /**
     * Services that can be instantiated without factories
     *
     * @var array
     */
    protected $invokables = [
        'SharedEventManager' => 'Zend\EventManager\SharedEventManager',
    ];

    /**
     * Service factories
     *
     * @var array
     */
    protected $factories = [
        'EventManager'  => 'Zend\Mvc\Service\EventManagerFactory',
        'ModuleManager' => 'Zend\Mvc\Service\ModuleManagerFactory',
    ];

    /**
     * Abstract factories
     *
     * @var array
     */
    protected $abstractFactories = [];

    /**
     * Aliases
     *
     * @var array
     */
    protected $aliases = [
        'Zend\EventManager\EventManagerInterface'     => 'EventManager',
        'Zend\ServiceManager\ServiceLocatorInterface' => 'ServiceManager',
        'Zend\ServiceManager\ServiceManager'          => 'ServiceManager',
    ];

    /**
     * Shared services
     *
     * Services are shared by default; this is primarily to indicate services
     * that should NOT be shared
     *
     * @var array
     */
    protected $shared = [
        'EventManager' => false,
    ];

    /**
     * Delegators
     *
     * @var array
     */
    protected $delegators = [];

    /**
     * Initializers
     *
     * @var array
     */
    protected $initializers = [];

    /**
     * Constructor
     *
     * Merges internal arrays with those passed via configuration
     *
     * @param  array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->initializers = [
            'EventManagerAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
                if ($instance instanceof EventManagerAwareInterface) {
                    $eventManager = $instance->getEventManager();

                    if ($eventManager instanceof EventManagerInterface) {
                        $eventManager->setSharedManager($serviceLocator->get('SharedEventManager'));
                    } else {
                        $instance->setEventManager($serviceLocator->get('EventManager'));
                    }
                }
            },
            'ServiceManagerAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
                if ($serviceLocator instanceof ServiceManager && $instance instanceof ServiceManagerAwareInterface) {
                    $instance->setServiceManager($serviceLocator);
                }
            },
            'ServiceLocatorAwareInitializer' => function ($instance, ServiceLocatorInterface $serviceLocator) {
                if ($instance instanceof ServiceLocatorAwareInterface) {
                    $instance->setServiceLocator($serviceLocator);
                }
            },
        ];

        $this->factories['ServiceManager'] = function (ServiceLocatorInterface $serviceLocator) {
            return $serviceLocator;
        };

        parent::__construct(ArrayUtils::merge(
            [
                'invokables'         => $this->invokables,
                'factories'          => $this->factories,
                'abstract_factories' => $this->abstractFactories,
                'aliases'            => $this->aliases,
                'shared'             => $this->shared,
                'delegators'         => $this->delegators,
                'initializers'       => $this->initializers,
            ],
            $configuration
        ));
    }
}
