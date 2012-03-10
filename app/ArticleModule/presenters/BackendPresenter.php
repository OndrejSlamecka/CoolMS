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
		return new ArticleForm($this->repositories->Article, $this->repositories->Article_draft);
	}

}
