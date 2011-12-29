<?php
/**
 * This file is a part of the NDBF library
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License can be found within the file license.txt in the root folder.
 * 
 */

namespace NDBF;

class RepositoryManager
{

    /** @var Nette\DI\Container */
    private $container;

    /** @var Nette\Database\Connection */
    private $connection;

    /** @var array */
    private $instantiated_repositories;

    /* ------------------------ CONSTRUCTOR, DESIGN ------------------------- */

    public function __construct(\Nette\DI\Container $container, \Nette\Database\Connection $connection)
    {
        $this->container = $container;
        $this->connection = $connection;
    }

    /**
     * Returns instance of Application\Repository\<$repository> if exists else instance of NDBF\Repository
     * @param string Repository name
     * @return NDBF\Repository
     */
    public function getRepository($name)
    {
        if (empty($this->instantiated_repositories) || !in_array($name, array_keys($this->instantiated_repositories))) {
            $class = 'Application\\Repository\\' . $name;

            if (class_exists($class)) {
                $instance = new $class($this->container, $this->connection, $name);
            } else {
                $instance = new Repository($this->container, $this->connection, $name);
            }
            $this->instantiated_repositories[$name] = $instance;
        }
        return $this->instantiated_repositories[$name];
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