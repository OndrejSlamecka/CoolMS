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

    /** @var FileModule/PathHandler */
    private $pathHandler;

    public function startup()
    {
        parent::startup();
        $this->pathHandler = new PathHandler($this->context->parameters['wwwDir'], '/files');
    }

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->mode = self::MODE_LIST;
        return $template;
    }

    public function getPathHandler()
    {
        return $this->pathHandler;
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
            $this->template->fullpath = $this->pathHandler->getFullPath();
            $this->template->folder_above = '/';
            $this->template->breadcrumbs = null;

            // In search mode path is filename
            $this->template->items = \Nette\Utils\Finder::findFiles('*' . $path . '*')->from($this->pathHandler->getFullPath());
        } else {
            $this->template->path = $path = Paths::sanitize($path);
            $this->template->fullpath = $fullpath = $this->pathHandler->getFullPath($path);
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
        $fullpath = $this->pathHandler->getFullPath($path);


        if (!file_exists($fullpath)) {
            $this->flashMessage('The file or folder you are trying to delete was not found');
        } else {

            if (is_file($fullpath)) {
                $message = 'File deleted';
            } else {
                $message = 'Folder deleted';
            }

            Files::remove($fullpath);

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
        $form = new \Application\Form($this, $name);
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

        foreach ($form['files'] as $file) {
            Files::move($this->pathHandler->getFullPath($path), $file);
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
        $path = Paths::sanitize($this->getParam('path'));
        $form = $form->getValues();

        $folder = $form['folder'];

        mkdir($this->pathHandler->getFullPath($path . '/' . $form['folder']));

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
        $fhandler = $this->pathHandler;
        $showpath = Paths::sanitize(dirname($this->getParam('path')));

        $form = $form->getValues();

        $newName = Paths::sanitize($form['new_name']);
        $oldName = Paths::sanitize($form['old_name']);

        $newName = dirname($oldName) . '/' . $newName;

        $newName = $fhandler->getFullPath($newName);
        $oldName = $fhandler->getFullPath($oldName);
        Files::rename($oldName, $newName);

        $this->setTemplateVariables($showpath);
        $this->invalidateControl('FileList');

        if (!$this->isAjax())
            $this->redirect('default', array('path' => $showpath));
    }

    public function createComponentSearchForm($name)
    {
        $form = new \Application\Form($this, $name);

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

            // TODO: Implement search without AJAX
            /* if (!$this->isAjax())
              $this->redirect('search', array('path' => $showpath)); */
        }

        $this->invalidateControl('FileList');
    }

}
