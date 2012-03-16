<?php
/**
 * This file is a part of the NDBF library
 *
 * @copyright (c) Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License can be found within the file license.txt in the root folder.
 *
 */

namespace NDBF;

class RepositoryManager
{

	/** @var Nette\DI\Container */
	private $container;

	/** @var array */
	private $instantiatedRepositories;


	/* ------------------------ CONSTRUCTOR, DESIGN ------------------------- */

	public function __construct(\Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Returns instance of Application\Repository\<$repository> if exists else instance of NDBF\Repository
	 * @param string Repository name
	 * @return NDBF\Repository
	 */
	public function getRepository($name)
	{
		if ($this->container->hasService('ndbf.repositories.' . $name)) {
			return $this->container->getService('ndbf.repositories.' . $name);
		} else {
			if (empty($this->instantiatedRepositories) || !in_array($name, array_keys($this->instantiatedRepositories))) {
				$instance = new Repository($this->container->getByType('Nette\Database\Connection'), $name);
				$this->instantiatedRepositories[$name] = $instance;
			}
			return $this->instantiatedRepositories[$name];
		}
	}

	/**
	 * Getter and shortuct for getRepository()
	 * @param string Repository name
	 * @return NDBF\Repository
	 */
	public function __get($name)
	{
		return $this->getRepository($name);
	}

}