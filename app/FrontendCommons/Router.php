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

use Nette\Application\Routers\Route;

class Router extends \Coolms\Frontend\Router
{

	/**
	 * FRONT ROUTES ARE EDITED HERE
	 * @param Nette\Application\Routers\RouteList $router
	 */
	public function addRoutes(&$router)
	{
		$modules = $this->modules->getModules();

		// If you are curious how the $modules array looks, just
		// dump($modules); die;

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

}
