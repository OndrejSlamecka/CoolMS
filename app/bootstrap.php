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

$config->setupServices();
$config->setupSession();
$config->setupRouting();

$container = $config->getContainer();

if (!$container->params['consoleMode']) {

    $config->setupApplication();

    // Run the app
    $container->application->run();
}