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
 * File manager's presenter
 * TODO: Refactor. sanitizePath, getRelativePath, getFullPath, getFolderAbove, recursiveRemoveDir should be moved into some model
 * 
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{
    const MODE_SEARCH = 'search';
    const MODE_LIST = 'list';

    private $mode;

    /** @var FileModule/FileHandler */
    private $fileHandler;

    public function startup()
    {
        parent::startup();
        $this->fileHandler = new FileHandler($this->context->parameters['wwwDir'], '/files');
    }

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->mode = self::MODE_LIST;
        return $template;
    }

    public function getFileHandler()
    {
        return $this->fileHandler;
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
            $this->template->fullpath = $this->fileHandler->getFullPath();
            $this->template->folder_above = '/';
            $this->template->breadcrumbs = null;

            // In search mode path is filename
            $this->template->items = \Nette\Utils\Finder::findFiles('*' . $path . '*')->from($this->fileHandler->getFullPath());
        } else {
            $this->template->path = $path = $this->fileHandler->sanitizePath($path);
            $this->template->fullpath = $fullpath = $this->fileHandler->getFullPath($path);
            $this->template->folder_above = $this->fileHandler->getFolderAbove($path);
            $this->template->breadcrumbs = $this->prepareBreadcrumbs($path);
            $this->template->items = \Nette\Utils\Finder::find('*')->in($fullpath);
        }
    }

    public function handleEdit($path)
    {
        $this->template->editingItem = $path;

        // We want to show folder above
        $path = $this->fileHandler->getFolderAbove($path);

        $this->setTemplateVariables($path);

        $this['renameForm']->setDefaults(array('old_name' => $this->template->editingItem));

        $this->invalidateControl('FileList');
    }

    public function actionDelete($path)
    {
        $path = $this->fileHandler->sanitizePath($path);
        $fullpath = $this->fileHandler->getFullPath($path);


        if (!file_exists($fullpath)) {
            $this->flashMessage('The file or folder you are trying to delete was not found');
        } else {

            if (is_file($fullpath)) {
                unlink($fullpath);
                $this->flashMessage('File deleted');
            } elseif (is_dir($fullpath)) {

                $this->fileHandler->recursiveRemoveDir($fullpath);
                $this->flashMessage('Folder deleted');
            } else {
                $this->flashMessage('Something strange happened, please try again.');
            }
        }

        $this->redirect("default", array('path' => $this->fileHandler->getFolderAbove($path)));
    }

    public function renderDefault($path)
    {
        if (!isset($this->template->path)) {
            $this->setTemplateVariables($path);
        }
    }

    public function createComponentFileUploadForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('html5upload');

        //$form->addUpload('file', 'Soubor');
        $form->addMultipleUpload("files", "File(s)");

        $form->addSubmit('send', 'Upload');
        $form['send']->getControlPrototype()->class('big');

        $form->onSuccess[] = array($this, 'fileUploadFormSubmit');

        return $form;
    }

    public function fileUploadFormSubmit($form)
    {
        $path = $this->fileHandler->sanitizePath($this->getParam('path'));
        $form = $form->getValues();

        foreach ($form['files'] as $file) {
            // Get name and path to place
            $filename = Strings::webalize($file->name, '.');
            $filepath = $this->fileHandler->getFullPath($this->fileHandler->sanitizePath($path)) . '/' . $filename;
            // Move
            $file->move($filepath, $filename);
        }

        if ($this->isAjax()) {
            // $this->invalidateControl('flash'); // Uncomment if you want to show some flash messages
            $this->invalidateControl('FileList');
        } else {
            $this->redirect('default', array('path' => $path));
        }
    }

    public function createComponentFolderCreationForm($name)
    {
        $form = new \Application\Form($this, $name);

        $form->addText('folder', 'Folder name');

        $form->addSubmit('send', 'Create');
        $form['send']->getControlPrototype()->class('big');

        $form->onSuccess[] = array($this, 'folderCreationFormSubmit');

        return $form;
    }

    public function folderCreationFormSubmit($form)
    {
        $path = $this->fileHandler->sanitizePath($this->getParam('path'));
        $form = $form->getValues();

        $folder = $form['folder'];

        mkdir($this->fileHandler->getFullPath($path . '/' . $form['folder']));

        $this->redirect('default', array('path' => $path));
    }

    public function createComponentRenameForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('ajax');

        $form->addText('new_name', 'Name');
        $form['new_name']->getControlPrototype()->class('low');

        $form->addHidden('old_name');

        $form->addImage('send', $this->template->themePath . '/icons/accept.png', 'Save');
        $form['send']->getControlPrototype()->class('low');

        $form->onSuccess[] = array($this, 'renameFormSubmit');

        return $form;
    }

    public function renameFormSubmit($form)
    {
        $fhandler = $this->fileHandler;
        $showpath = $fhandler->sanitizePath($fhandler->getFolderAbove($this->getParam('path')));

        $form = $form->getValues();

        $fhandler->rename($form['old_name'], $form['new_name']);

        $this->setTemplateVariables($showpath);
        $this->invalidateControl('FileList');

        if (!$this->isAjax())
            $this->redirect('default', array('path' => $showpath));
    }

    public function createComponentSearchForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('ajax onchange');

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

            if (!$this->isAjax())
                $this->redirect('search', array('path' => $showpath));
        }

        $this->invalidateControl('FileList');
    }

}
