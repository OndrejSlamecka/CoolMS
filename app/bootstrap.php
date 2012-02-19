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
 *    - Backend
 *       - One universal route + image browser route are enough
 *    - Frontend
 *       - Routes defined in RouteManager
 */
$router = $container->router;

if ($container->parameters['consoleMode']) {
    $router = new \Nette\Application\Routers\SimpleRouter();
} else {

    // Backend module - image-browser + universal
    $router[] = new Route('imgbrowser_cached_thumbnails/<url .+>',
                    array('module' => 'File', 'presenter' => 'ImageBrowser', 'action' => 'cache',
                        'url' => array( Route::FILTER_IN => NULL, Route::FILTER_OUT => NULL, ),
                        ));
    $router[] = new Route('admin/file/image-browser',
                    array('module' => 'File', 'presenter' => 'ImageBrowser', 'action' => 'default'));
    $router[] = new Route('admin/<module>/<action>[/<id>]',
                    array('module' => 'Dashboard', 'presenter' => 'Backend', 'action' => 'default'));

    // Frontend
    $frontRoutemanager = new \Frontend\RouteManager($container);
    $frontRoutemanager->addRoutes($router);
}

/* --- RUN THE APP --- */
if (!$container->parameters['consoleMode'])
    $container->application->run();
