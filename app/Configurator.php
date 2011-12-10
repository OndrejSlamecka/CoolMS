<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Application;

class Configurator extends \Nette\Configurator
{

    /** STATIC - container independent * */
    public static function setupDebugger()
    {
        \Nette\Diagnostics\Debugger::$logDirectory = __DIR__ . '/../log';
        \Nette\Diagnostics\Debugger::$strictMode = TRUE;
        \Nette\Diagnostics\Debugger::enable();
    }

    /* INSTANTIATED */

    public function __construct($params)
    {
        parent::__construct();
        $this->container->params += $params;
        $this->loadConfig($this->container->params['appDir'] . '/config.neon');
    }

    public function setupServices()
    {
        // RobotLoader
        $this->container->addService(
                'robotLoader', function( $container ) {
                    return \Nette\Configurator::createServiceRobotLoader(
                                    $container, array('directory' =>
                                array(0 => $container->params['appDir'],
                                    1 => $container->params['libsDir'])
                                    )
                    ); // createService
                } // function
        ); // addService 
        // Run
        $this->container->robotLoader;

        // Other services
        $this->container->addService('authenticator', new \Backend\Authenticator($this->container));

        $this->container->addService('presenterTree', new \Kdyby\PresenterTree($this->container));

        $this->container->addService('moduleManager', new ModuleManager($this->container));

        list($dsn, $user, $password) = $this->container->params['database'];
        $this->container->addService('database', \NDBF\Factory::createService($this->container, $dsn, $user, $password));

        $this->container->addService('repositoryManager', new \NDBF\RepositoryManager($this->container));
    }

    public function setupSession()
    {
        $this->container->session->start();
    }

    public function setupApplication()
    {
        $this->container->application->errorPresenter = 'Error';
    }

    public function setupRouting()
    {
        $router = $this->container->router;

        if ($this->container->params['consoleMode']) {
            // CONSOLE MODE
            $router = new \Nette\Application\Routers\SimpleRouter();
        } else {
            // NOT CONSOLE MODE       
            // Admin module // TODO: Move into separate class?
            $router[] = new \Nette\Application\Routers\Route('admin/<module>/<action>[/<id>]', array(
                        'module' => 'Dashboard',
                        'presenter' => 'Backend',
                        'action' => 'default'
                    ));

            // Frontend
            $frontRoutemanager = new \Frontend\RouteManager($this->container);
            $frontRoutemanager->addRoutes($router);
        }
    }

}