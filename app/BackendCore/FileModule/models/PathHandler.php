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

use \Nette\Utils\Strings,
    \Application\Utils\Paths;

/**
 * Class responsible for determining paths
 *
 * $pathHandler = new PathHandler('e:/www', '/example');
 * $pathHandler->getFullPath(); // "e:/www/example"
 * $pathHandler->getRelativePath('/subdir'); // "/example/subdir"
 */
class PathHandler extends \Nette\Object
{

    private $absolutePathToRoot;
    private $relativePath;

    /**
     *
     * @param string $absolutePathToRoot Absolute path to PathHandler's working directory
     * @param string $relativePath Relative path within PathHandler's working directory
     */
    public function __construct($absolutePathToRoot, $relativePath='/')
    {
        $this->absolutePathToRoot = $absolutePathToRoot;
        $this->relativePath = $relativePath;

        if (!file_exists($this->absolutePathToRoot . $this->relativePath) && !mkdir($this->absolutePathToRoot . $this->relativePath, 0777))
            throw new \Nette\InvalidArgumentException("Creation of directory " . $this->absolutePathToRoot . $this->relativePath . " was not successful");
    }

    /**
     * Returns <pathhandler relative path>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getRelativePath($path = NULL)
    {
        return str_replace('//', '/', $this->relativePath . Paths::sanitize($path));
    }

    /**
     * Returns <pathhandler absolute path>/<pathhandler relative path>/<path> for given path.
     * @param string $path
     * @return string
     */
    public function getFullPath($path = NULL)
    {
        return str_replace('//', '/', $this->absolutePathToRoot . $this->getRelativePath($path));
    }

}