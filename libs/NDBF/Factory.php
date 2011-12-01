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

class Factory extends \Nette\Object
{

    public static function createService(\Nette\DI\Container $container, $dsn, $user, $password)
    {
        $db = new \Nette\Database\Connection($dsn, $user, $password);
        $db->setCacheStorage($container->cacheStorage);
        return $db;
    }

}