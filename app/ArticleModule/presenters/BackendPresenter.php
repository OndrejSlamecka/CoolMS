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

    public function actionDelete($id, $draft=false)
    {
        if ($draft)
            $articles = $this->repositories->Article_draft;
        else
            $articles = $this->repositories->Article;

        $article = $articles->find(array('id' => $id))->fetch();

        if (!$article) {
            $this->flashMessage('Article not found');
            $this->redirect('default');
        }

        try {
            $articles->delete(array('id' => $id));

            // Save for reverse
            $this->sessionSection->reversableItem = $article->toArray();
            $this->sessionSection->reversableItem['is_draft'] = $draft;

            $this->flashMessage('Article deleted &ndash; <a href="' . $this->link('reverseDelete') . '" >Undo</a>');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    public function actionReverseDelete()
    {
        $article = $this->sessionSection->reversableItem;

        if ($article['is_draft'])
            $articles = $this->repositories->Article_draft;
        else
            $articles = $this->repositories->Article;

        unset($article['is_draft']);

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

    public function renderEdit($id, $draft)
    {
        if ($draft)
            $articles = $this->repositories->Article_draft;
        else
            $articles = $this->repositories->Article;

        $article = $articles->find(array('id' => $id))->fetch();

        $this->template->article = $article;

        if (!$article) {
            $this->flashMessage('Requested article was not found');
            $this->redirect('default');
        }

        if ($article instanceof \Nette\Database\Table\ActiveRow)
            $article = $article->toArray();

        $article += array('article_id' => $article['id']);

        $this['articleForm']->setDefaults($article);
    }

    public function renderDefault()
    {
        $articles = $this->repositories->Article;
        $this->template->articles = $articles->find();

        $articles_drafts = $this->repositories->Article_draft;
        $this->template->articles_drafts = $articles_drafts->find();
    }

    public function createComponentArticleForm($name)
    {
        $form = new \Application\Form($this, $name);
        $form->getElementPrototype()->class('savable');

        $form->addHidden('id');
        $form->addHidden('article_id');
        $form->addHidden('user_id');

        $form->addText('name_webalized', 'Name in URL');

        $form->addText('name', 'Name');
        $form['name']->getControlPrototype()->class('short');

        $form->addTextarea('text', 'Text', 60, 30);
        $form['text']->getControlPrototype()->class('wysiwyg');

        $form->addSubmit('save', 'Save')->getControlPrototype()->class('emphasized');
        $form->addSubmit('publish', 'Publish')->getControlPrototype()->class('emphasized');

        $form->onSuccess[] = array($this, 'articleSubmit');

        return $form;
    }

    public function articleSubmit($form)
    {
        $articles = $this->repositories->Article;
        $articles_drafts = $this->repositories->Article_draft;

        if ($form['publish']->isSubmittedBy()) {
            $isPublishing = true;
        } else { // save button, ajax
            $isPublishing = false;
        }

        $article = $form->getValues();

        // It is useless to autosave empty articles
        if ($this->isAjax() && $article['name'] === '' && $article['text'] === '') {
            $this->terminate();
        }


        if ($article['id'] === '') {
            $article['id'] = null;
            $article['user_id'] = $this->getUser()->getId();
        } else {
            // Preserve original date
            $orig_record = $articles->find(array('id' => $article['id']))->fetch();
            //$article['date'] = $orig_record['date'];
        }

        if ($article['article_id'] === '')
            $article['article_id'] = null;

        if ($isPublishing) {
            $draft_id = $article['id'];
            $article['id'] = $article['article_id'];
            unset($article['article_id']);
            $article['date'] = new \DateTime;
        }

        if ($article['name_webalized'] === '')
            $article['name_webalized'] = \Nette\Utils\Strings::webalize($article['name']);

        try {

            if ($isPublishing) {
                $articles->save($article, 'id');
                $articles_drafts->delete(array('id' => $draft_id));
            } else {
                $articles_drafts->save($article, 'id');
            }

            if ($this->isAjax()) {
                $this->payload->draft_id = $article['id'];
            } else {
                $this->flashMessage('Article saved.');
            }
        } catch (\Exception $e) {

            if ($this->isAjax()) {
                $this->payload->error = TRUE;
                $this->terminate();
            } else {
                $this->flashMessage('Article was not saved. Please try again and then contact the administrator.');
            }
        }

        $this->redirect('default');
    }

}
