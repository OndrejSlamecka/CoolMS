<?php

namespace Coolms;

use \Nette\Utils\Strings;

class CompilerExtension extends \Nette\Config\CompilerExtension
{

	public function loadConfiguration()
	{
		// shortcut cf means data are from config
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		/* Modules */

		$cfModules = $config['modules'];
		$modules = array();

		foreach ($cfModules as $name => $settings) {
			$modules[$name] = array();
			if (isset($settings['publicName'])) {
				$modules[$name]['name'] = $settings['publicName'];
				$cfViews = $settings['views'];
			} else {
				$modules[$name]['name'] = Strings::firstUpper($name);
				$cfViews = $settings;
			}

			$views = array();
			foreach ($cfViews as $internalName => $publicName) {

				if (is_int($internalName)) { // [1]=> string(7) "archive"
					$internalName = $publicName;
					$publicName = Strings::firstUpper($publicName);
				}
				$views[$internalName] = $publicName;
			}

			$modules[$name]['views'] = $views;
		}

		// CoolMS service
		$builder->addDefinition($this->prefix('modules'))
				->setClass('Coolms\Modules', array('@nette.presenterFactory', $modules));
	}

}
