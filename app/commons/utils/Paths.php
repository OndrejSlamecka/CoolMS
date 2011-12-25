<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Application\Utils;

use \Nette\Utils\Strings;

class Paths extends \Nette\Object
{

    /**
     * Converts backslashes to forward slashes, removes redundant slashes and double dots ("..")
     * @param string $path
     * @return string 
     */
    public static function sanitize($path)
    {
        // Use just '/' everywhere        
        $path = str_replace('\\', '/', $path); // Strings::replace($path, '~\\\~', '/');
        // Use just one separator... 
        $path = str_replace('\/\/', '/', $path); // Strings::replace($path, '~\/\/~', '/');
        // Remove ..
        $path = str_replace('..', '', $path); // Strings::replace($path, '~\.\.~', '');

        if ($path === '')
            $path = '/';

        return $path;
    }

    /**
     * Returns folder above given path
     * @param string $path
     * @return string 
     */
    public static function getFolderAbove($path)
    {
        // If not base folder
        if ($path !== '/') {
            $folder_above = Strings::match($path, "~(.*)/.*$~");
            if (is_array($folder_above))
                $folder_above = array_pop($folder_above);
            else // If it is not array, it is empty - just one dir over base                
                $folder_above = '/';
        }else
            $folder_above = '/';

        return $folder_above;
    }

}