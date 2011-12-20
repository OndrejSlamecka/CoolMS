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

/**
 * TODO: Refactor: getRelativePath, getFullPath, sanitizePath, getFolderAbove VERSUS recursiveRemoveDir, rename, moveFile
 *                 |--------------------- Path methods ---------------------|        |------ file, folder methods ------|
 */
class FileHandler extends \Nette\Object
{

    private $baseDir;
    private $relativePath;

    /**
     *
     * @param string $baseDir
     * @param string $relativePath 
     */
    public function __construct($baseDir, $relativePath='/')
    {
        $this->baseDir = $baseDir;
        $this->relativePath = $relativePath;

        if (!file_exists($this->baseDir . $this->relativePath))
            throw new \Nette\InvalidArgumentException('Given relative path must be an existing folder under the given base directory.');
    }

    /**
     * Returns <filehandler work dir>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getRelativePath($path = null)
    {
        return $this->relativePath . $path;
    }

    /**
     * Returns <filesystem path>/<relative path>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getFullPath($path = null)
    {
        return $this->baseDir . $this->getRelativePath($path);
    }

    /**
     * Converts backslashes to forward slashes, removes redundant slashes and double dots ("..")
     * @param string $path
     * @return string 
     */
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

    /**
     * Returns folder above given path
     * @param string $path
     * @return string 
     */
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

    /**
     * Recursively removes directory and its contents
     * @param type $dir 
     */
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

    /**
     * Renames file $oldName to $newName
     * @param string $oldName
     * @param string $newName
     * @return bool True on success, false on failure
     */
    public function rename($oldName, $newName)
    {
        $newName = $this->sanitizePath($newName);
        $oldName = $this->sanitizePath($oldName);

        $newName = $this->getFolderAbove($oldName) . '/' . $newName;

        $newName = $this->getFullPath($newName);
        $oldName = $this->getFullPath($oldName);

        if (file_exists($oldName))
            return rename($oldName, $newName);
    }

    /**
     * Sanitizes path and moves file to path/filename
     * @param \Nette\Http\FileUpload $file 
     */
    public function moveFile($path, \Nette\Http\FileUpload $file)
    {
        // Get name and path to place
        $filename = Strings::webalize($file->name, '.');
        $filepath = $this->getFullPath($this->sanitizePath($path)) . '/' . $filename;
        // Move
        $file->move($filepath, $filename);
    }

}