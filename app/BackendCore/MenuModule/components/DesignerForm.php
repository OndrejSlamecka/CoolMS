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

class DesignerForm extends \Coolms\Form
{

	/** @var \Nette\ComponentModel\IContainer */
	private $parent;

	/** @var \Coolms\Repository\Menuitem */
	private $menuitems;

	/**
	 * @param \Nette\ComponentModel\IContainer
	 * @param string
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Coolms\Repository\Menuitem $menuitemRepository)
	{
		$this->parent = $parent;
		$this->menuitems = $menuitemRepository;
		parent::__construct($parent, $name);
	}

	public function setup()
	{
		$this->getElementPrototype()->class('designerControlForm');

		$this->addHidden('structure');
		$this->addSubmit('save', 'Save menu order');

		$this->onSuccess[] = array($this, 'designerSubmit');
	}

	private function treeToArrayOfOrder($branch)
	{
		if (empty($branch))
			return array();

		$order = array();
		for ($i = 1; $i <= count($branch); $i++) {
			list($itemId, $children) = each($branch);
			$itemId = (int) \Nette\Utils\Strings::replace($itemId, "~^mi-([0-9]+)~", "$1");
			$order[$itemId] = $i;
			$order = $order + $this->{__FUNCTION__}($children);
		}
		return $order;
	}

	private function treeToArrayOfRelations($branch, $parent = NULL)
	{
		if (empty($branch))
			return array();

		$relations = array();
		for ($i = 1; $i <= count($branch); $i++) {
			list($itemId, $children) = each($branch);
			$itemId = (int) \Nette\Utils\Strings::replace($itemId, "~^mi-([0-9]+)~", "$1");
			$relations[$itemId] = $parent;
			$relations = $relations + $this->{__FUNCTION__}($children, $itemId);
		}
		return $relations;
	}

	public function designerSubmit($form)
	{
		$form = $form->getValues();
		$tree = $form['structure'];

		$tree = json_decode($tree, true);

		try {
			$order = $this->treeToArrayOfOrder($tree);
			$this->menuitems->orderUpdate($order);

			$relations = $this->treeToArrayOfRelations($tree);
			$this->menuitems->parentsUpdate($relations);

			$this->parent->flashMessage('Changes saved');
		} catch (Exception $e) {
			$this->parent->flashMessage('Saving of changes was not successful, please try again');
		}

		$this->parent->redirect('default');
	}

}
