<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace FileModule;

use \Nette\Utils\Strings;

class FileHandler extends \Nette\Object
{

    private $baseDir;
    private $relativePath;

    public function __construct($baseDir, $relativePath='/')
    {
        $this->baseDir = $baseDir;
        $this->relativePath = $relativePath;
    }

    public function getRelativePath($path = null)
    {
        return $this->relativePath . $path;
    }

    public function getFullPath($path = null)
    {
        return $this->baseDir . $this->getRelativePath($path);
    }

    public function sanitizePath($path)
    {
        // Use just '/' everywhere
        $path = Strings::replace($path, '~\\\~', '/');
        // Use just one separator... 
        $path = Strings::replace($path, '~\/\/~', '/');
        // Remove ..
        $path = Strings::replace($path, '~\.\.~', '');

        if ($path === '')
            $path = '/';

        return $path;
    }

    public function getFolderAbove($path)
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

    private function recursiveRemoveDir($dir)
    {
        /* http://www.php.net/manual/en/function.rmdir.php#98622 */

        $objects = scandir($dir);

        if (is_array($objects)) // scandir returns false for empty folders
            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..")
                    if (filetype($dir . "/" . $object) === "dir")
                        $this->recursiveRemoveDir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
            }
        rmdir($dir);
    }

    public function rename($oldName, $newName)
    {
        $newName = $this->sanitizePath($newName);
        $oldName = $this->sanitizePath($oldName);

        $newName = $this->getFolderAbove($oldName) . '/' . $newName;

        $newName = $this->getFullPath($newName); // Necessary?
        $oldName = $this->getFullPath($oldName);

        if (file_exists($oldName))
            return rename($oldName, $newName);
    }

}