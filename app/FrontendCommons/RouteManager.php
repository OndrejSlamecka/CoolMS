<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Frontend;

use Nette\Application\Routers\Route,
	Nette\Utils\Strings,
	Coolms\Utils\Arrays;

/**
 * Front module routing.
 *
 * @author Ondrej Slamecka
 */
class RouteManager extends \Nette\Object
{

	/** @var Coolms\Modules */
	private $modules;

	/** @var Application\Repository\Menuitem */
	private $menu;

	function __construct(\NDBF\Repository $menuitem, \Coolms\Modules $modules)
	{
		$this->menu = $menuitem;

		$modules = clone $modules;
		$modules->setModules(Arrays::mapRecursive(callback('\Nette\Utils\Strings::webalize'), $modules->getModules()));
		$this->modules = $modules;
	}

	/**
	 *
	 * @param Nette\Application\Routers\RouteList $router
	 */
	public function addRoutes(&$router)
	{
		$modules = $this->modules->getModules();

		/* FRONT ROUTES ARE EDITED HERE */

		// Index
		$router[] = new Route('', $this->getIndexMetadata());

		// Module: Page
		$router[] = new Route($modules['Page']['name'] . '/<name>',
						$this->formMetadata('Page', 'default')
		);

		// Module: Article

		$router[] = new Route($modules['Article']['name'],
						$this->formMetadata('Article', 'default')
		);

		// webalize so that cool-uri will appear, not any unfriendly characters
		$router[] = new Route($modules['Article']['name'] . '/' . $modules['Article']['views']['archive'],
						$this->formMetadata('Article', 'archive')
		);

		$router[] = new Route($modules['Article']['name'] . '/<name>',
						$this->formMetadata('Article', 'detail')
		);

		// The rest...
		$router[] = new Route('<module>/<action>[/<name>]',
						$this->getIndexMetadata()
		);
	}

	/* METHODS */

	/**
	 * Forms metadata into form accepted by Nette\Application\Routers\Route. Adds translation tables
	 * @param string $module
	 * @param string $action
	 * @param array $args
	 * @return array
	 */
	public function formMetadata($module, $action = null, $args = null)
	{
		$modulesNames = $this->modules->getModulesNames();
		$modulesNames = array_flip($modulesNames);

		$metadata = array('presenter' => 'Frontend',
			'module' => array(
				Route::VALUE => $module,
				Route::FILTER_TABLE => $modulesNames,
			)
		);

		$moduleViews = $this->modules->getViews($module);
		$moduleViews = array_flip($moduleViews);

		if ($action !== null) {
			$metadata += array('action' => array(
					Route::VALUE => $action,
					Route::FILTER_TABLE => $moduleViews
				)
			);
		}

		if (is_array($args))
			$metadata += $args;

		return $metadata;
	}

	/* Index data */

	public function getIndexViewParams()
	{
		$index = $this->menu->getIndex();

		if (empty($index['module_view_argument']))
			return;

		$viewParams = $index['module_view_argument'];

		if (!empty($viewParams)) {
			$paramsPairs = explode(';', $viewParams);
			$viewParams = array();
			foreach ($paramsPairs as $pair) {
				$pair = explode('=', $pair);
				$viewParams[$pair[0]] = $pair[1];
			}
		}

		return $viewParams;
	}

	public function getIndexMetadata()
	{
		$index = $this->menu->getIndex();

		$modulesNames = $this->modules->getModulesNames();

		$presenter = array(
			Route::VALUE => $index['module_name'],
			Route::FILTER_TABLE => $modulesNames
		);

		$front_default = array(
			'presenter' => 'Frontend',
			'module' => $presenter,
			'action' => $index['module_view']
		);

		$front_default += (array) $this->getIndexViewParams(); // (array) because of possible null value

		return $front_default;
	}

}
