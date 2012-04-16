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

use Nette\Forms\Form,
	\Nette\Utils\Strings;

class ArticleForm extends \Coolms\Form
{

	/** @var \NDBF\Repository */
	private $articles;

	/** @var \NDBF\Repository */
	private $drafts;

	public function __construct(\NDBF\Repository $articles, \NDBF\Repository $drafts)
	{
		parent::__construct();
		$this->articles = $articles;
		$this->drafts = $drafts;
	}

	public function setup()
	{
		$this->getElementPrototype()->class('savable');

		$this->addHidden('id');
		$this->addHidden('article_id');
		$this->addHidden('user_id');

		$this->addText('name_webalized', 'Name in URL', 30)
				->getControlPrototype()->class('name_webalized');

		$this->addText('name', 'Name')
				->getControlPrototype()->class('name_webalized_source short');

		$this->addTextarea('text', 'Text', 60, 30);
		$this['text']->getControlPrototype()->class('wysiwyg');

		$this->addSubmit('save', 'Save as draft')->getControlPrototype()->class('emphasized');
		$this->addSubmit('publish', 'Publish')->getControlPrototype()->class('emphasized');

		$this->onSuccess[] = array($this, 'success');
	}

	public function success($form)
	{
		$articles = $this->articles;
		$articles_drafts = $this->drafts;

		if ($form['publish']->isSubmittedBy()) {
			$isPublishing = true;
		} else { // save button, ajax
			$isPublishing = false;
		}

		$article = $form->getValues();

		// It is useless to autosave empty articles
		if ($this->presenter->isAjax() && $article['name'] === '' && $article['text'] === '') {
			$this->presenter->terminate();
		}

		if ($article['id'] === '') {
			$article['id'] = null;
			$article['user_id'] = $this->presenter->getUser()->getId();
		} else {
			// Preserve original date
			$orig_record = $articles->select()->where('id', $article['id'])->fetch();
			//$article['date'] = $orig_record['date']; // TODO: ?
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
			$article['name_webalized'] = Strings::webalize($article['name']);
		else
			$article['name_webalized'] = Strings::webalize($article['name_webalized']); // Never trust user input

		try {

			if ($isPublishing) {
				$articles->save($article, 'id');
				$articles_drafts->delete(array('id' => $draft_id));
			} else {
				$articles_drafts->save($article, 'id');
			}

			if ($this->presenter->isAjax()) {
				$this->presenter->payload->draft_id = $article['id'];
			} else {
				$this->presenter->flashMessage('Article saved.');
			}
		} catch (\Exception $e) {

			if ($this->presenter->isAjax()) {
				$this->presenter->payload->error = TRUE;
				$this->presenter->terminate();
			} else {
				$this->presenter->flashMessage('Article was not saved. Please try again and then contact the administrator.');
			}
		}

		$this->presenter->redirect('default');
	}

}
