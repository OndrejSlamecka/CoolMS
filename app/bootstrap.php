<?php

// Load nette and setup
require LIBS_DIR . '/Nette/loader.php';
require APP_DIR . '/config/Configurator.php';

// Debugging
Application\Configurator::setupDebugger();

// Configuration
$config = new Application\Configurator(LIBS_DIR, APP_DIR);

$config->setupRouting();

$container = $config->getContainer();

if (!$container->parameters['consoleMode']) {

    $config->setupApplication();

    // Run the app
    $container->application->run();
}