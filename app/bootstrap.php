<?php
use \Nette\Application\Routers\Route;
// Load Nette
require LIBS_DIR . '/Nette/loader.php';

// Set up configurator and debugging
$configurator = new \Nette\Config\Configurator();
$configurator->setTempDirectory(APP_DIR . '/../temp');
$configurator->enableDebugger(__DIR__ . '/../log');

// (Auto)Load _ALL_ the classes
$robotLoader = $configurator->createRobotLoader()
        ->addDirectory(LIBS_DIR)
        ->addDirectory(APP_DIR)
        ->register();

// Add configuration file to configurator and create container
$configurator->addConfig(APP_DIR . '/config/config.neon');
$container = $configurator->createContainer();

// Register RobotLoader as a service
$container->addService('robotLoader', $robotLoader);

/* --- ROUTING --- */
/*
 * Console mode
 *    - SimpleRouter
 *
 * Development / Production mode
 *    - \Backend\RouteManager
 *    - \Frontend\RouteManager
 */
$router = $container->router;

if ($container->parameters['consoleMode']) {
    $router = new \Nette\Application\Routers\SimpleRouter();
} else {

    // Backend module
    \Backend\RouteManager::addRoutes($router);

    // Frontend
    $frontRoutemanager = new \Frontend\RouteManager($container);
    $frontRoutemanager->addRoutes($router);
}

/* --- RUN THE APP --- */
if (!$container->parameters['consoleMode'])
    $container->application->run();
