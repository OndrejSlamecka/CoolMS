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

class FileModel extends \Nette\Object
{

	/**
	 * Contains the absolute path to the root (e.g. e:/www)
	 * @var string
	 */
	private $root;

	/**
	 * Contains the relative path within the root directory (e.g. /userfiles)
	 * @var string
	 */
	private $path;

	public function __construct($root, $path)
	{
		$this->root = $root;
		$this->path = $path;
	}

	/* ------------------------------- GETTERS ------------------------------ */

	/**
	 * @return type Full path
	 */
	public function getAbsolutePath()
	{
		return $this->root . $this->path;
	}

	/**
	 * @return type Part of path after root directory
	 */
	public function getRelativePath()
	{
		return $this->path;
	}

	/* ------------------------------- METHODS ------------------------------ */

	/**
	 * Tells whether file/dir exists on given path within storage
	 * @param string $path
	 * @return bool
	 */
	public function exists($path = '')
	{
		return file_exists($this->getAbsolutePath() . $path);
	}

	/**
	 * Tells whether there is an existing file at given path
	 * @param string $path
	 * @return bool
	 */
	public function is_file($path)
	{
		return is_file($this->getAbsolutePath() . $path);
	}

	/**
	 * Counts files matching given pattern in storage
	 * @param string $pattern Optional. Default is "/*"
	 * @return int
	 */
	public function count($pattern = '/*')
	{
		return count(glob($this->getAbsolutePath() . $pattern));
	}

	/**
	 * Creates new folder at given path. If folder exists method does not do anything
	 * @param string $path
	 */
	public function createFolder($path = '/')
	{
		if ($path[0] !== '/')
			throw new \Nette\InvalidArgumentException('Given path "' . $path . '" does not start with slash.');

		$fullPath = $this->getAbsolutePath() . $path;

		if (is_dir($fullPath))
			return;

		if (!is_dir(dirname($fullPath)))
			throw new \Nette\DirectoryNotFoundException('Folder "' . $fullPath . '" cannot be created within non-existing path.');

		if (!mkdir($fullPath))
			throw new \Nette\IOException('Creation of folder "' . $fullPath . '" failed.');
	}

	/**
	 *
	 * @param Nette\Http\FileUpload $file
	 * @param string $path Path within FileModel's working path (root_directory.relative_path.$path)
	 * @return string Returns relative path to file within storage
	 */
	public function save(\Nette\Http\FileUpload $file, $path = '/')
	{
		if ($path[0] !== '/')
			throw new \Nette\InvalidArgumentException('Given path "' . $path . '" does not start with slash.');

		$filename = \Nette\Utils\Strings::webalize($file->name, '.');
		$relativePath = $path . '/' . $filename; // Path within storage
		$absolutePath = $this->getAbsolutePath() . $relativePath;

		$file->move($absolutePath, $filename);
		return $relativePath;
	}

	/**
	 * Renames $oldname to $newname
	 * @param string $oldname
	 * @param string $newname
	 */
	public function rename($oldname, $newname)
	{
		$oldname = $this->getAbsolutePath() . $oldname;
		$newname = $this->getAbsolutePath() . $newname;

		if (file_exists($oldname)) {
			if (!rename($oldname, $newname))
				throw new \Nette\IOException('Renaming failed');
		} else {
			throw new \Nette\InvalidArgumentException('There is no file at given path');
		}
	}

	/**
	 * Removes directory/file at given path
	 * @param string $path
	 */
	public function remove($path)
	{
		$path = $this->getAbsolutePath() . $path;

		if (is_file($path)) {
			unlink($path);
		} else {
			$files = Nette\Utils\Finder::find('*')->from($path)->childFirst();

			foreach ($files as $file)
				if ($file->isDir())
					rmdir($file->getPathname());
				else
					unlink($file->getPathname());

			rmdir($path);
		}
	}

}
