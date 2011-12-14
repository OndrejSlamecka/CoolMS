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

/**
 * An optional factory for Nette\Database\Connection
 */
class Factory extends \Nette\Object
{

    public static function createService($dsn, $user, $password, \Nette\Caching\IStorage $storage = null)
    {
        $db = new \Nette\Database\Connection($dsn, $user, $password);
        $db->setCacheStorage($storage);
        return $db;
    }

}
