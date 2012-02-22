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

    private $fileModel;
    private $imageCacheModel;

    public function startup()
    {
        parent::startup();
        $this->fileModel = $this->getService('userFiles');
        $this->imageCacheModel = $this->getService('userImagesCache');
    }

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);

        $imageCacheModel = $this->imageCacheModel;
        $template->registerHelper('cache', function($url) use ($imageCacheModel) {
                    return $imageCacheModel->getRelativePath() . $url;
                });
        $template->fileModel = $this->fileModel;
        return $template;
    }

    public function actionCache($url)
    {
        $url = \Application\Utils\Paths::sanitize($url);

        try {
            $img_path = $this->fileModel->getAbsolutePath() . '/' . $url;
            $img = \Nette\Image::fromFile($img_path);
            $img->resize(100, 100);
            $cached_file_path = $this->imageCacheModel->getAbsolutePath() . '/' . $url;

            // Make sure all folders exist
            $chunks = explode('/', $url);
            array_pop($chunks); // Last item is the file
            $recomposed_path = $this->imageCacheModel->getAbsolutePath();
            foreach ($chunks as $chunk) {
                $recomposed_path .= '/' . $chunk;
                if (!is_dir($recomposed_path))
                    mkdir($recomposed_path);
            }

            $img->save($cached_file_path, 80);
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
                ->in($this->fileModel->getAbsolutePath() . $path);
    }

}
