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

use \Nette\Diagnostics\Debugger;

class Configurator extends \Nette\Config\Configurator
{
    /* STATIC - container independent */

    public static function setupDebugger()
    {
        Debugger::$logDirectory = __DIR__ . '/../log';
        Debugger::$strictMode = TRUE;
        Debugger::enable();
    }

    /* INSTANTIATED */

    /**
     * Constructor.
     * @param string $libsDir
     * @param string $appDir 
     */
    public function __construct($libsDir, $appDir)
    {
        // Define dir for temporary files
        $tempDir = $appDir . '/../temp';

        // Construct itself and set cache
        parent::__construct();
        $this->setCacheDirectory($tempDir);

        // Define parameters and load config
        $this->addParameters(array('libsDir' => $libsDir, 'appDir' => $appDir, 'tempDir' => $tempDir));
        $this->loadConfig($appDir . '/config/config.neon');

        // Start session
        $this->container->session->start();
    }

    public function setupServices()
    {
        // Robot Loader
        $robotLoader = $this->createRobotLoader();
        $robotLoader->addDirectory($this->container->parameters['libsDir']);
        $robotLoader->addDirectory($this->container->parameters['appDir']);
        $robotLoader->register();

        $this->container->addService('robotLoader', $robotLoader);

        // Other services
        $this->container->addService('authenticator', new \Backend\Authenticator($this->container));
        $this->container->addService('presenterTree', new \Kdyby\PresenterTree($this->container));
        $this->container->addService('moduleManager', new ModuleManager($this->container));

        list($dsn, $user, $password) = $this->container->parameters['database'];
        $this->container->addService('database', \NDBF\Factory::createService($dsn, $user, $password, $this->container->cacheStorage));

        $this->container->addService('repositoryManager', new \NDBF\RepositoryManager($this->container));
    }

    public function setupApplication()
    {
        $this->container->application->errorPresenter = 'Error';
    }

    public function setupRouting()
    {
        $router = $this->container->router;

        /*
         * Console mode
         *    - SimpleRouter
         * 
         * Development / Production mode
         *    - Backend
         *       - One universal route is enough
         *    - Frontend
         *       - Routes defined in RouteManager
         */

        if ($this->container->parameters['consoleMode']) {

            $router = new \Nette\Application\Routers\SimpleRouter();
        } else {

            // Backend module
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