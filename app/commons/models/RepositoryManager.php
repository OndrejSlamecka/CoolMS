<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Application;

class RepositoryManager extends \NDBF\RepositoryManager
{

	/** @var \Nette\DI\Container */
	private $container;

	public function setContainer(\Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	protected function onRepositoryCreated(\NDBF\Repository $instance)
	{
		parent::onRepositoryCreated($instance);
		if (method_exists($instance, 'setContainer'))
			$instance->setContainer($this->container);
	}

}