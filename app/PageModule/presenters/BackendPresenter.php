<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace PageModule;

/**
 * Page presenter
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BaseItemPresenter
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
			$pages->delete(array('id' => $id));

			// Save for reverse
			$this->sessionSection->reversableItem = $page->toArray();

			$this->flashMessage('Page deleted &ndash; <a href="' . $this->link('reverseDelete') . '" >Undo</a>');
		} catch (Exception $e) {
			$this->flashMessage('Something went wrong, please try again');
		}
		$this->redirect('default');
	}

	public function actionReverseDelete()
	{
		$page = $this->sessionSection->reversableItem;
		$pages = $this->repositories->Page;

		try {
			$pages->save($page, 'id');

			$this->flashMessage('Page "' . htmlspecialchars($page['name']) . '" was restored'); // Not sure if htmlspecialchars is needed, check later

			unset($this->sessionSection->reversableItem);
		} catch (Exception $e) {
			$this->flashMessage("I am sorry, but the page could not be restored");
		}

		$this->redirect("default");
	}

	public function renderEdit($id, $autosave=false)
	{
		$pages = $this->repositories->Page;

		if ($autosave)
			$page = $this->sessionSection->autosave;
		else
			$page = $pages->find(array('id' => $id))->fetch();

		$this->template->page = $page;

		if (!$page) {
			$this->flashMessage('Requested page was not found');
			$this->redirect('default');
		}

		if ($page instanceof \Nette\Database\Table\Selection)
			$page = $page->toArray();

		$this['pageForm']->setDefaults($page);
	}

	public function renderDefault()
	{
		$pages = $this->repositories->Page;
		$this->template->pages = $pages->find();
		$this->template->autosave = $this->sessionSection->autosave;
	}

	public function createComponentPageForm($name)
	{
		return new PageForm($this->repositories->Page, $this->sessionSection);
	}

}
