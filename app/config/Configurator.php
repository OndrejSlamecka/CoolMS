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

    /** @var \SystemContainer */
    private $container;

    /* STATIC - container independent */

    public static function setupDebugger()
    {
        Debugger::$logDirectory = __DIR__ . '/../../log';
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
        $this->setTempDirectory($tempDir);

        // Define parameters and add config
        $this->addParameters(array('libsDir' => $libsDir, 'appDir' => $appDir, 'tempDir' => $tempDir));
        $this->addConfig($appDir . '/config/config.neon');

        // RobotLoader
        $robotLoader = $this->createRobotLoader();
        $robotLoader->addDirectory($libsDir);
        $robotLoader->addDirectory($appDir);
        $robotLoader->register(); // Load _ALL_ the classes

        // Create container
        $this->container = $this->createContainer();

        // Add RobotLoader as a service
        $this->container->addService('robotLoader', $robotLoader);

        // Start session
        $this->container->session->start();
    }

    /**
     * @return \SystemContainer
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function setupApplication()
    {
        $this->container->application->errorPresenter = 'Error';

        if ($this->container->parameters['productionMode'])
            $this->container->application->catchExceptions = TRUE;
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