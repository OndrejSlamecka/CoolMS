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

use \Nette\Utils\Strings;

class Helpers
{

    public static function loader($helper)
    {
        $callback = callback(__CLASS__, $helper);
        if ($callback->isCallable())
            return $callback;
    }

    /*** HELPERS ***/

    /**
     * Returns extension of given file
     * @param string $filename
     * @return string
     */
    public static function extension($filename)
    {
        $m = Strings::match($filename, "~\.([a-zA-Z0-9]+)$~");
        if (is_array($m))
            return Strings::lower(array_pop($m));
        else
            return $m;
    }

    /**
     * Generates thumbnail path for given path. E.g. from pic.jpg it makes pic_small.jpg
     * @param string $path
     * @return string
     */
    public static function thumbnailpath($path)
    {
        return preg_replace("~\.([a-zA-Z]+)$~", "_small.$1", $path);
    }

    /**
     * Removes $part from given parameter
     * @param string $txt
     * @param string $part
     * @return string
     */
    public static function remove($txt, $part)
    {
        return str_replace($part, "", $txt);
    }

}
