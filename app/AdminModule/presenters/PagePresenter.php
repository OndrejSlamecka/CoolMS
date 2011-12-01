<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace AdminModule;

use Nette\Forms\Form;

/**
 * Page presenter
 *
 * @author Ondrej Slamecka
 */
class PagePresenter extends BasePresenter
{

    public function actionDelete($id)
    {
        $pages = $this->repositories->Page;

        $page = $pages->find(array('id' => $id))->fetch();

        if (!$page) {
            $this->flashMessage('Page not found');
            $this->redirect('default');
        }

        try {
            $pages->remove(array('id' => $id));
            $this->flashMessage('Page deleted');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout('wysiwyg_layout');
    }

    public function renderEdit($id)
    {
        $pages = $this->repositories->Page;

        $this->template->page = $page = $pages->find(array('id' => $id))->fetch();


        if (!$page) {
            $this->flashMessage('Requested page was not found');
            $this->redirect('default');
        }

        $arr = $page->toArray();

        $this['pageForm']->setDefaults($arr);
    }

    public function renderDefault()
    {
        $pages = $this->repositories->Page;
        $this->template->pages = $pages->find();
    }

    public function createComponentPageForm($name)
    {
        $form = new \App\Form($this, $name);
        $form->getElementPrototype()->class('textFormatForm');

        $form->addHidden('id');

        $form->addText('name_webalized', 'Name in URL');

        $form->addText('name', 'Název');
        $form['name']->getControlPrototype()->class('ribbon');

        $form->addTextarea('text', 'Text', 60, 30);
        $form['text']->getControlPrototype()->class('wysiwyg');

        $form->addText('template', 'Template');

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = array($this, 'pageSubmit');

        return $form;
    }

    public function pageSubmit($form)
    {
        $page = $form->getValues();

        if ($page['id'] === '')
            $page['id'] = null;
        if ($page['name_webalized'] === '')
            $page['name_webalized'] = \Nette\Utils\Strings::webalize($page['name']);

        $pages = $this->repositories->Page;

        try {
            $pages->save($page, 'id');
            $this->flashMessage('Page saved');
        } catch (Exception $e) {
            $this->flashMessage('Article was not saved. Please try again and then contact the administrator');
        }

        $this->redirect('default');
    }

}
