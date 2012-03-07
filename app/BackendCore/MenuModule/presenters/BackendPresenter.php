<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace MenuModule;

use Application\Entity\Menuitem;

/**
 * Menu manager
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{

	public function actionDelete($id)
	{
		$menuitems = $this->repositories->Menuitem;

		$item = $menuitems->find(array('id' => $id))->fetch();

		if (!$item) {
			$this->flashMessage('Item was not found');
			$this->redirect('default');
		}

		try {
			$menuitems->delete(array('id' => $id));
			$this->flashMessage('Item removed');
		} catch (Exception $e) {
			$this->flashMessage('Something went wrong, please try again');
		}
		$this->redirect('default');
	}

	public function renderDefault()
	{
		$menu = $this->repositories->Menuitem;
		$this->template->items = $menu->fetchStructured();
	}

	/* --------------------------- MENU ITEM FORM --------------------------- */

	public function handleEdit($id)
	{
		$this['menuitemForm']->toggleEditing($id);
		$this->invalidateControl('MenuitemFormSnippet');
	}

	public function createComponentMenuitemForm($name)
	{
		$moduleManager = $this->getService('coolms.modules');
		$menuitemRepository = $this->getService('repositoryManager')->Menuitem;
		return new MenuitemForm($this, $moduleManager, $menuitemRepository);
	}

	/* ----------------- MENU DESIGNER CONTROL (STRUCTURE) ------------------ */

	public function createComponentDesignerControlForm($name)
	{
		return new DesignerForm($this, $name, $this->getService('repositoryManager')->Menuitem);
	}

}
