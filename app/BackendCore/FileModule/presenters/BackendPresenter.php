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
    \Coolms\Utils\Paths;

/**
 * File manager's presenter
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{
    const MODE_SEARCH = 'search';
    const MODE_LIST = 'list';

    private $mode;

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->mode = self::MODE_LIST;
        return $template;
    }

    /**
     *
     * @return Coolms\FileModel
     */
    public function getFileModel()
    {
        return $this->getService('userFiles');
    }

    public function prepareBreadcrumbs($path)
    {
        if (empty($path) || $path === '/')
            return array('/' => '/');

        $splitPath = explode('/', $path);
        array_shift($splitPath); // Remove first empty string (before first "/")
        $pathStack = '';
        $breadcrumbs = array();

        foreach ($splitPath as $piece) {

            $pathStack .= '/' . $piece;
            $breadcrumbs[$pathStack] = $piece;
        }

        return array('/' => '/') + $breadcrumbs;
    }

    public function setTemplateVariables($path)
    {
        if ($this->mode === self::MODE_SEARCH) {
            $this->template->path = '/';
            $this->template->fullpath = $this->fileModel->getAbsolutePath();
            $this->template->folder_above = '/';
            $this->template->breadcrumbs = null;

            // In search mode path is filename
            $this->template->items = \Nette\Utils\Finder::findFiles('*' . $path . '*')->from($this->fileModel->getAbsolutePath());
        } else {
            $this->template->path = $path = Paths::sanitize($path);
            $this->template->fullpath = $fullpath = $this->fileModel->getAbsolutePath() . $path;
            $this->template->folder_above = dirname($path);
            $this->template->breadcrumbs = $this->prepareBreadcrumbs($path);
            $this->template->items = \Nette\Utils\Finder::find('*')->in($fullpath);
        }
    }

    public function handleEdit($path)
    {
        $this->template->editingItem = $path;

        // We want to show folder above
        $path = dirname($path);

        $this->setTemplateVariables($path);

        $this['renameForm']->setDefaults(array('old_name' => $this->template->editingItem));

        $this->invalidateControl('FileList');
    }

    public function actionDelete($path)
    {
        $path = Paths::sanitize($path);

        if (!$this->fileModel->exists($path)) {
            $this->flashMessage('The file or folder you are trying to delete was not found');
        } else {

            if ($this->fileModel->is_file($path)) {
                $message = 'File deleted';
            } else {
                $message = 'Folder deleted';
            }

            $this->fileModel->remove($path);

            // Remove file from cache
            $imagesCacheModel = $this->getService('userImagesCache');
            if ($imagesCacheModel->exists($path))
                $imagesCacheModel->remove($path);

            $this->flashMessage($message);
        }

        $this->redirect("default", array('path' => dirname($path)));
    }

    public function renderDefault($path)
    {
        if (!isset($this->template->path)) {
            $this->setTemplateVariables($path);
        }
    }

    public function createComponentFileUploadForm($name)
    {
        $form = new \Coolms\Form($this, $name);
        $form->getElementPrototype()->class('html5upload');

        //$form->addUpload('files', 'File');
        $form->addMultipleUpload("files", "File(s)");

        $form->addSubmit('send', 'Upload');
        $form['send']->getControlPrototype()->class('big');

        $form->onSuccess[] = array($this, 'fileUploadFormSubmit');

        return $form;
    }

    public function fileUploadFormSubmit($form)
    {
        $path = Paths::sanitize($this->getParam('path'));
        $form = $form->getValues();

        if ($path === '/' && $this->fileModel->count() === 0)
            $wasEmpty = true;
        else
            $wasEmpty = false;

        foreach ($form['files'] as $file) {
            $relativePath = $this->fileModel->save($file, $path);

            if ($file->isImage()) {
                $imagesCacheModel = $this->getService('userImagesCache');
                if ($imagesCacheModel->exists($relativePath))
                    $imagesCacheModel->remove($relativePath);
            }
        }

        if ($this->isAjax() && !$wasEmpty) {
            // $this->invalidateControl('flash'); // Uncomment if you want to show some flash messages
            $this->invalidateControl('FileList');
        } else {
            $this->redirect('default', array('path' => $path));
        }
    }

    public function createComponentFolderCreationForm($name)
    {
        $form = new \Coolms\Form($this, $name);

        $form->addText('folder', 'Folder name');

        $form->addSubmit('send', 'Create');
        $form['send']->getControlPrototype()->class('big');

        $form->onSuccess[] = array($this, 'folderCreationFormSubmit');

        return $form;
    }

    public function folderCreationFormSubmit($form)
    {
        $path = Paths::sanitize($this->getParam('path'));
        $form = $form->getValues();

        $folder = $form['folder'];

        $this->fileModel->createFolder(Paths::sanitize($path . '/' . $form['folder']));

        $this->redirect('default', array('path' => $path));
    }

    public function createComponentRenameForm($name)
    {
        $form = new \Coolms\Form($this, $name);
        $form->getElementPrototype()->class('ajax');

        $form->addText('new_name', 'Name');
        $form['new_name']->getControlPrototype()->class('low');

        $form->addHidden('old_name');

        $form->addImage('send', $this->template->commonsPath . '/icons/accept.png', 'Save');
        $form['send']->getControlPrototype()->class('low');

        $form->onSuccess[] = array($this, 'renameFormSubmit');

        return $form;
    }

    public function renameFormSubmit($form)
    {
        $showpath = Paths::sanitize(dirname($this->getParam('path')));

        $form = $form->getValues();

        $newName = Paths::sanitize($form['new_name']);
        $oldName = Paths::sanitize($form['old_name']);

        $newName = dirname($oldName) . $newName;

        $this->fileModel->rename($oldName, $newName);

        // Rename file in cache
        $imagesCacheModel = $this->getService('userImagesCache');
        if ($imagesCacheModel->exists($oldName))
            $imagesCacheModel->rename($oldName, $newName);


        $this->setTemplateVariables($showpath);
        $this->invalidateControl('FileList');

        if (!$this->isAjax())
            $this->redirect('default', array('path' => $showpath));
    }

    public function createComponentSearchForm($name)
    {
        $form = new \Coolms\Form($this, $name);

        $form->addText('q', 'Search');
        $form['q']->getControlPrototype()->addAttributes(array('autocomplete' => 'off'));

        $form->onSuccess[] = array($this, 'searchFormSubmit');

        return $form;
    }

    public function searchFormSubmit($form)
    {
        $form = $form->getValues();

        if (empty($form['q'])) {
            $this->setTemplateVariables('/');
        } else {
            $this->mode = self::MODE_SEARCH;
            $this->template->mode = self::MODE_SEARCH;

            $this->setTemplateVariables($form['q']);
        }

        $this->invalidateControl('FileList');
    }

}
