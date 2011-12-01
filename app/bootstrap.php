<?php
// Set directories
$params['libsDir'] = $params['appDir'] . '/../libs';
$params['tempDir'] = $params['appDir'] . '/../temp';

// Load nette and setup
require $params['libsDir'] . '/Nette/loader.php';
require $params['appDir'] . '/Configurator.php';

// Debugging
App\Configurator::setupDebugger();

// Configuration
$config = new App\Configurator($params);

$container = $config->setupContainer();

$config->setupRobotloader();
$config->setupServices();
$config->setupSession();
$config->setupRouting();

if (!$container->params['consoleMode']) {

    $config->setupApplication();

    // Run the app
    $container->application->run();
}