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

class Files extends \Nette\Object
{

    /**
     * If $path is a file removes it. If it is a dir, it removes dir and its contents.
     * @param type $path Path to file or directory
     */
    public static function remove($path)
    {
        /* http://www.php.net/manual/en/function.rmdir.php#98622 */

        if (is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $objects = scandir($path);

            if (is_array($objects)) // scandir returns false for empty folders
                foreach ($objects as $object) {
                    if ($object !== "." && $object !== "..")
                        if (filetype($path . "/" . $object) === "dir")
                            self::remove($path . "/" . $object);
                        else
                            unlink($path . "/" . $object);
                }
            rmdir($path);
        }else {
            throw new \Nette\InvalidArgumentException('There is no file or directory at given path');
        }
    }

    /**
     * Renames file $oldName to $newName
     * @param string $oldName
     * @param string $newName
     * @return bool True on success, false on failure
     */
    public static function rename($oldName, $newName)
    {       
        if (file_exists($oldName)) {
            if (!rename($oldName, $newName))
                throw new \Nette\IOException('Renaming failed');
        } else {
            throw new \Nette\InvalidArgumentException('There is no file at given path');
        }
    }

    /**
     * Sanitizes path and moves file to path/filename
     * @param \Nette\Http\FileUpload $file 
     */
    public static function move($path, \Nette\Http\FileUpload $file)
    {
        // Get name and path to place
        $filename = Strings::webalize($file->name, '.');
        $filepath = Paths::sanitize($path) . '/' . $filename;
        // Move
        $file->move($filepath, $filename);
    }

}