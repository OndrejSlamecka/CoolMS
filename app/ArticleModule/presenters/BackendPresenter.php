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

            // Save for reverse
            $this->sessionSection->reversableItem = $article->toArray();

            $this->flashMessage('Article deleted &ndash; <a href="' . $this->link('reverse') . '" >Undo</a>');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    public function actionReverse()
    {
        $article = $this->sessionSection->reversableItem;
        $articles = $this->repositories->Article;

        try {
            $articles->save($article, 'id');

            // Not sure if htmlspecialchars is needed, check later
            $this->flashMessage('Article "' . htmlspecialchars($article['name']) . '" was restored');

            unset($this->sessionSection->reversableItem);
        } catch (Exception $e) {
            $this->flashMessage("I am sorry, but the article could not be restored");
        }

        $this->redirect("default");
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout($this->context->parameters['appDir'] . '/BackendCommons/templates/@wysiwyg_layout.latte');
    }

    public function renderEdit($id, $autosave=false)
    {
        $articles = $this->repositories->Article;

        if ($autosave)
            $article = $this->sessionSection->autosave;
        else
            $article = $articles->find(array('id' => $id))->fetch();

        $this->template->article = $article;

        if (!$article) {
            $this->flashMessage('Requested article was not found');
            $this->redirect('default');
        }

        if ($article instanceof \Nette\Database\Table\Selection)
            $article = $article->toArray();

        $this['articleForm']->setDefaults($article);
    }

    public function renderDefault()
    {
        $article = $this->repositories->Article;
        $this->template->articles = $article->find();
        $this->template->autosave = $this->sessionSection->autosave;
    }

    public function createComponentArticleForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('savable');

        $form->addHidden('id');

        $form->addText('name_webalized', 'Name in URL');

        $form->addText('name', 'Name');
        $form['name']->getControlPrototype()->class('ribbon');

        $form->addTextarea('text', 'Text', 60, 30);
        $form['text']->getControlPrototype()->class('wysiwyg');

        $form->addSubmit('save', 'Save')
                ->getControlPrototype()->class('emphasized');

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

            if ($this->isAjax()) {
                $this->sessionSection->autosave = $article;
            } else {
                unset($this->sessionSection->autosave);
                $articles->save($article, 'id');
            }

            $this->flashMessage('Article saved.');
        } catch (\Exception $e) {
            $this->flashMessage('Article was not saved. Please try again and then contact the administrator');

            if ($this->isAjax()) {
                $this->payload->error = TRUE;
                $this->terminate();
            }
        }

        $this->redirect('default');
    }

}
