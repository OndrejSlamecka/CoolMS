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
    \Application\Utils\Files,
    \Application\Utils\Paths;

class ImageBrowserPresenter extends \Backend\BasePresenter
{

    private $filesPath;
    private $cachePath;

    public function startup()
    {
        parent::startup();
        $this->filesPath = $this->getService('userFilesPath');
        $this->cachePath = $this->getService('userImagesCachePath');
    }

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);

        $cachePath = $this->cachePath;
        $template->registerHelper('cache', function($url) use ($cachePath) {
                    return $cachePath->getRelativePath() . $url;
                });
        $template->filesPath = $this->filesPath;
        return $template;
    }

    public function actionCache($url)
    {
        $url = \Application\Utils\Paths::sanitize($url);

        try {
            $img_path = $this->filesPath->getFullPath() . $url;
            $img = \Nette\Image::fromFile($img_path);
            $img->resize(100, 100);
            $cache_path = $this->cachePath->getFullPath() . $url;

            // Make sure all folders exist
            $chunks = explode('/', $url);
            array_pop($chunks); // Last item is the file
            $recomposed_path = $this->cachePath->getFullPath();
            foreach ($chunks as $chunk) {
                $recomposed_path .= '/' . $chunk;
                if (!is_dir($recomposed_path))
                    mkdir($recomposed_path);
            }

            $img->save($cache_path, 80);
            $this->redirect('this');
        } catch (\Nette\UnknownImageFileException $e) {
            $this->terminate();
        } catch (\Nette\InvalidArgumentException $e) {
            $this->terminate();
        }
    }

    public function renderDefault($path = '/')
    {
        $path = \Application\Utils\Paths::sanitize($path);

        $this->template->path = $path;
        $this->template->items = \Nette\Utils\Finder::find('*')
                ->filter(function($file) {
                            return $file->isDir() || (bool) @getimagesize($file->getPathname()); // intentionally @
                        })
                ->in($this->filesPath->getFullPath() . $path);

        // cache dir imgbrowser_cached_thumbnails
    }

}
