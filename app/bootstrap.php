<?php

// Load nette and setup
require LIBS_DIR . '/Nette/loader.php';
require APP_DIR . '/Configurator.php';

// Debugging
Application\Configurator::setupDebugger();

// Configuration
$config = new Application\Configurator(LIBS_DIR, APP_DIR, APP_DIR.'/../temp');

$config->setupServices();
$config->setupRouting();

$container = $config->getContainer();

if (!$container->params['consoleMode']) {

    $config->setupApplication();

    // Run the app
    $container->application->run();
}