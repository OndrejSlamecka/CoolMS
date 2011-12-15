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

class Configurator extends \Nette\Config\Configurator
{

    public $directories;

    /** STATIC - container independent * */
    public static function setupDebugger()
    {
        \Nette\Diagnostics\Debugger::$logDirectory = __DIR__ . '/../log';
        \Nette\Diagnostics\Debugger::$strictMode = TRUE;
        \Nette\Diagnostics\Debugger::enable();
    }

    /* INSTANTIATED */

    public function __construct($libsDir, $appDir, $tempDir)
    {
        $this->directories = array('libsDir' => $libsDir, 'appDir' => $appDir, 'tempDir' => $tempDir);
        parent::__construct();
        $this->setCacheDirectory($this->directories['tempDir']);
        $this->loadConfig($this->directories['appDir'] . '/config.neon');
    }

    public function setupServices()
    {
        // robotLoader
        $robotLoader = $this->createRobotLoader();

        $robotLoader->addDirectory($this->directories['libsDir']);
        $robotLoader->addDirectory($this->directories['appDir']);

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

        if ($this->container->parameters['consoleMode']) {
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