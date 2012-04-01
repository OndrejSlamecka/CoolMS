<?php

// Load Nette
require LIBS_DIR . '/nette/nette/Nette/loader.php';

// Set up configurator, temporary files dir and debugging
$configurator = new \Nette\Config\Configurator();
$configurator->setTempDirectory(__DIR__ . '/../temp')
		->enableDebugger(__DIR__ . '/../log');

// Autoload _ALL_ the classes
$configurator->createRobotLoader()
		->addDirectory(LIBS_DIR)
		->addDirectory(__DIR__) // appDir
		->register();

// Add CoolMS and NDBF compiler extension
$configurator->onCompile[] = function ($configurator, $compiler) {
			$compiler->addExtension('coolms', new Coolms\CompilerExtension);
			$compiler->addExtension('ndbf', new NDBF\CompilerExtension);
		};

// Add configuration file to configurator and create container
$configurator->addConfig(__DIR__ . '/config/config.neon');
$container = $configurator->createContainer();


/* --- ROUTING --- */
/*
 * Development / Production mode
 *    - \Backend\Router located in BackendCommons/Router.php
 *    - \Frontend\Router located in FrontendCommons/Router.php
 */
$router = $container->router;

if ($container->parameters['consoleMode']) {
	$router = new \Nette\Application\Routers\SimpleRouter();
} else {

	// Backend module
	\Backend\Router::addRoutes($router);

	// Frontend
	$frontRouter = new \Frontend\Router($container->getService('ndbf.repositoryManager')->Menuitem, $container->getService('coolms.modules'));
	$frontRouter->addRoutes($router);
}

/* --- RUN THE APP --- */
if (!$container->parameters['consoleMode'])
	$container->application->run();
