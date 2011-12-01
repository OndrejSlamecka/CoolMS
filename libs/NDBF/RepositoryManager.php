<?php
/**
 * This file is a part of the NDBF library
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace NDBF;

class RepositoryManager
{

    /** @var Nette\DI\Container */
    private $repositoryContainer;
    private $instantiated_repositories;

    /************************** CONSTRUCTOR, DESIGN ***************************/

    public function __construct(\Nette\DI\Container $container)
    {
        $this->repositoryContainer = $container;
    }

    public function getRepository($repository)
    {
        if (empty($this->instantiated_repositories) || !in_array($repository, array_keys($this->instantiated_repositories))) {
            $repo_class = 'App\\Repository\\' . $repository;

            if (class_exists($repo_class)) {
                $repo = new $repo_class($this->repositoryContainer, $repository);
            } else {
                $repo = new Repository($this->repositoryContainer, $repository);
            }
            $this->instantiated_repositories[$repository] = $repo;
        }
        return $this->instantiated_repositories[$repository];
    }

    public function __get($name)
    {
        return $this->getRepository($name);
    }

}