<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Coolms;

class Modules extends \Nette\Object
{

	/** @var Nette\Application\PresenterFactory  */
	private $presenterFactory;

	/** @var array */
	private $modules;

	public function __construct(\Nette\Application\PresenterFactory $presenterFactory, $modules)
	{
		$this->presenterFactory = $presenterFactory;
		$this->modules = $modules;
	}

	public function getModules()
	{
		return $this->modules;
	}

	public function getModulesNames()
	{
		$arr = array();
		foreach ($this->modules as $name => $settings) {
			$arr[$name] = $settings['name'];
		}
		return $arr;
	}

	public function getViews($module)
	{
		return $this->modules[$module]['views'];
	}

	/**
	 * Calls get<$view>ViewPossibleParams from given module
	 * @param string $module
	 * @param string $view
	 * @return mixed
	 */
	public function getViewParameters($module, $view)
	{
		$presenter = $this->presenterFactory->createPresenter($module . ':Frontend');

		$method_name = 'get' . ucfirst($view) . 'ViewPossibleParams';
		return $presenter->$method_name();
	}

	/* --- */

	/**
	 * Don't abuse!
	 */
	public function setModules(array $modules)
	{
		$this->modules = $modules;
	}

}