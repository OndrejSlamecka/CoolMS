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

class FrontendPresenter extends \Frontend\BasePresenter
{

	public function renderDefault()
	{
		$this->template->articles = $this->repositories->Article->select();
	}

	public function getDefaultViewPossibleParams()
	{
		return null;
	}

	public function renderArchive()
	{
		$articles = $this->repositories->Article;
		$archive = array();

		// Make sure AS is upper case - required by Nette\Database
		$selection = $articles->select()->order('date DESC');
		foreach ($selection as $article) {
			$archive[$article['date']->format('F Y')][$article['id']] = $article;
		}

		$this->template->archive = $archive;
	}

	public function getArchiveViewPossibleParams()
	{
		return null;
	}

	public function renderDetail($name)
	{
		$articles = $this->repositories->Article;
		$this->template->article = $articles->select()->where('name_webalized', $name)->fetch();
	}

	public function getDetailViewPossibleParams()
	{
		return null;
	}

}
