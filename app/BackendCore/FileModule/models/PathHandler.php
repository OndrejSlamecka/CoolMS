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
use \Application\Utils\Paths;

/**
 * Class responsible for determining paths
 */
class PathHandler extends \Nette\Object
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

        if (!file_exists($this->baseDir . $this->relativePath) && !mkdir($this->baseDir . $this->relativePath, 0777))
            throw new \Nette\InvalidArgumentException("Creation of directory " . $this->baseDir . $this->relativePath . " was not successful");
    }

    /**
     * Returns <filehandler work dir>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getRelativePath($path = null)
    {
        return Paths::sanitize($this->relativePath . Paths::sanitize($path));
    }

    /**
     * Returns <filesystem path>/<relative path>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getFullPath($path = null)
    {
        return Paths::sanitize($this->baseDir . $this->getRelativePath($path));
    }

}