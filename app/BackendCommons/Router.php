<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Backend;

use Nette\Application\Routers\Route;

class Router extends \Nette\Object
{

	public static function addRoutes(&$router)
	{
		// Image browser - previews to be cached
		$router[] = new Route('imgbrowser_cached_thumbnails/<url .+>',
						array('module' => 'File',
							'presenter' => 'ImageBrowser',
							'action' => 'cache',
							'url' => array(
								Route::FILTER_IN => NULL,
								Route::FILTER_OUT => NULL,
							),
				));

		// Image browser
		$router[] = new Route('admin/file/image-browser',
						array(
							'module' => 'File',
							'presenter' => 'ImageBrowser',
							'action' => 'default',
						)
		);

		// Everything else
		$router[] = new Route('admin/<module>/<action>[/<id>]',
						array(
							'module' => 'Dashboard',
							'presenter' => 'Backend',
							'action' => 'default',
						)
		);
	}

}
