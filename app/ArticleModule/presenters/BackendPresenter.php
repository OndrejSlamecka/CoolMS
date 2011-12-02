<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace ArticleModule;

use Nette\Forms\Form;

/**
 * Article presenter
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BaseItemPresenter
{

    public function actionDelete($id)
    {
        $articles = $this->repositories->Article;

        $article = $articles->find(array('id' => $id))->fetch();

        if (!$article) {
            $this->flashMessage('Article not found');
            $this->redirect('default');
        }

        try {
            $articles->remove(array('id' => $id));
            $this->flashMessage('Article deleted');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout($this->context->params['appDir'] . '/BackendCommons/templates/@wysiwyg_layout.latte');
    }

    public function renderEdit($id)
    {
        $articles = $this->repositories->Article;

        $this->template->article = $article = $articles->find(array('id' => $id))->fetch();


        if (!$article) {
            $this->flashMessage('Requested article was not found');
            $this->redirect('default');
        }

        $arr = $article->toArray();

        $this['articleForm']->setDefaults($arr);
    }

    public function renderDefault()
    {
        $article = $this->repositories->Article;
        $this->template->articles = $article->find();
    }

    public function createComponentArticleForm($name)
    {
        $form = new \App\Form($this, $name);
        $form->getElementPrototype()->class('textFormatForm');

        $form->addHidden('id');

        $form->addText('name_webalized', 'Name in URL');

        $form->addText('name', 'Name');
        $form['name']->getControlPrototype()->class('ribbon');

        $form->addTextarea('text', 'Text', 60, 30);
        $form['text']->getControlPrototype()->class('wysiwyg');

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = array($this, 'articleSubmit');

        return $form;
    }

    public function articleSubmit($form)
    {
        $article = $form->getValues();

        if ($article['id'] === '') {
            $article['id'] = null;
            $article['date'] = new \DateTime;
        }
        if ($article['name_webalized'] === '')
            $article['name_webalized'] = \Nette\Utils\Strings::webalize($article['name']);

        $articles = $this->repositories->Article;

        try {
            $articles->save($article, 'id');
            $this->flashMessage('Article saved.');
        } catch (Exception $e) {
            $this->flashMessage('Article was not saved. Please try again and then contact the administrator');
        }

        $this->redirect('default');
    }

}
