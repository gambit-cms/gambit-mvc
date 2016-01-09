<?php
namespace Gambit\Mvc\Listener;

use Zend\Authentication\AuthenticationService;
use Zend\Db\TableGateway\TableGateway;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Zend\View\Model\ConsoleModel;
use Zend\View\Model\ViewModel;

class OnBootstrapListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this, 'onBootstrap'));
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this, 'onPreBootstrap'), 100);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this, 'onPostBootstrap'), -100);
    }

    public function onBootstrap(MvcEvent $e)
    {
        
    }

    public function onPreBootstrap(MvcEvent $e) 
    {
        $application = $e->getApplication();
        $services    = $application->getServiceManager();
        $events      = $application->getEventManager();
        $config      = $services->has('Config') ? $services->get('Config') : [];

        // Check Database connectivity
        if ($services->has('Zend\Db\Adapter\Adapter')) {
            $adapter = $services->get('Zend\Db\Adapter\Adapter');

            try {
                $adapter->getDriver()->getConnection()->connect();
            } catch (\Exception $ex) {
                $viewManagerConfig = $config['view_manager'] ? $config['view_manager'] : [];
                $response          = $e->getResponse();
                
                $viewModel         = $e->getViewModel();
                $template          = isset($viewManagerConfig['service_unavailable_template']) ? $viewManagerConfig['service_unavailable_template'] : '503'; 
                $viewModel->setTemplate($template);
                
                if ($response instanceof HttpResponse) {
                    $response->setStatusCode(503);
                } elseif ($response instanceof ConsoleModel) {
                    $response->setErrorLevel(1);
                } else {
                    echo "Service Unavailable.";
                    exit(1);
                }

                $event = $e;
                $event->setResponse($response);
                $event->setTarget($application);

                $events->trigger(MvcEvent::EVENT_RENDER, $event);
                $events->trigger(MvcEvent::EVENT_FINISH, $event);

                $e->stopPropagation(true);
                exit(1);
            }
        }
        
        // Maintenance mode
        
        // Session Management
        if (!$services->has('Zend\Session\Config\ConfigInterface')) {
            if (isset($config['session_config'])) {
                $services->setFactory('Zend\Session\ConfigInterface', 'Zend\Session\Service\SessionConfigFactory');
            }
        }
        
        if (!$services->has('Zend\Session\Storage\StorageInterface')) {
            if (isset($config['session_storage'])) {
                $services->setFactory('Zend\Session\StorageInterface', 'Zend\Session\Service\StorageFactory');
            }
        }
        
        if (!$services->has('Zend\Session\SaveHandler\SaveHandlerInterface')) {
            if ($services->has('Zend\Db\Adapter\Adapter')) {
                $adapter        = $services->get('Zend\Db\Adapter\Adapter');
                $tableGateway   = new TableGateway('sessions', $adapter);
                $options        = new DbTableGatewayOptions();
                $sessionHandler = new DbTableGateway($tableGateway, $options);
                $services->setService('Zend\Session\SaveHandler\SaveHandlerInterface', $sessionHandler);
            }
        }

        if (!$services->has('Zend\Session\ManagerInterface')) {
            $services->setFactory('Zend\Session\ManagerInterface', 'Zend\Session\Service\SessionManagerFactory');
        }
    }

    public function onPostBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $services    = $application->getServiceManager();
        $events      = $application->getEventManager();

        // Authentication Service
        if (!$services->has('Zend\Authentication\AuthenticationService')) {
            $authenticationService = new AuthenticationService();
            $services->setService('Zend\Authentication\AuthenticationService', $authenticationService);
        }
    }
}