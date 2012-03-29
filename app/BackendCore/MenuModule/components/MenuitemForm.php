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

use \Coolms\Entity\Menuitem,
	\Nette\ComponentModel\IContainer,
	\Nette\Http\SessionSection;

class MenuitemForm extends \Coolms\Form
{

	/** @var \Nette\ComponentModel\IContainer */
	private $parent;

	/** @var \Coolms\Modules */
	private $moduleManager;

	/** @var \Coolms\Repository\Menuitem */
	private $menuitems;

	/**/
	private $editingMode;
	private $menuitemType;

	/**
	 *
	 * @param IContainer $parent
	 * @param \Coolms\Modules $moduleManager
	 * @param \Coolms\Repository\Menuitem $menuitemRepository
	 */
	public function __construct(IContainer $parent, \Coolms\Modules $moduleManager, \Coolms\Repository\Menuitem $menuitemRepository)
	{
		$this->parent = $parent;
		$this->moduleManager = $moduleManager;
		$this->menuitems = $menuitemRepository;

		$this->menuitemType = Menuitem::TYPE_MODULE;

		parent::__construct($parent, 'menuitemForm'); // calls setup
	}

	/* --------------------------- GENERAL METHODS -------------------------- */

	/**
	 * Turns the form into editing mode
	 */
	private function toggleEditingMode()
	{
		$this->editingMode = true;

		/* Form texts */
		$labels = array(
			'type' => 'Link is a',
			'module_name' => 'It\'s linking to the module',
			'module_view' => 'and it\'s view',
			'module_view_argument' => 'with argument',
			'module_caption' => 'titled',
			'submenu_caption' => 'titled',
			'menuitem_id' => 'Should it be in a submenu?',
			'strict_link_comparison' => 'Strict link comparison *',
			'save' => 'Save'
		);

		foreach ($labels as $control => $label) {
			$this[$control]->caption = $label;
		}
	}

	/**
	 * Turns the form into editing mode and sets item of given id as edited item
	 * @param int $id Item id
	 */
	public function toggleEditing($id)
	{
		$this->toggleEditingMode();

		/* Settings specific for the item being edited */
		$menuitem = $this->menuitems->select()->where('id', $id)->fetch();

		if ($menuitem['type'] === Menuitem::TYPE_MODULE) { // Module link
			$menuitem['module_caption'] = $menuitem['name'];

			// Sets module as default option and changes items in module_view
			$this->chooseModule($menuitem['module_name']);

			// Module view has probably changed - change module_view_arguments
			unset($this['module_view_argument']);
			$this->addModuleViewArgumentsInput($menuitem['module_name'], $menuitem['module_view']);
		} else { // Submenu
			$menuitem['submenu_caption'] = $menuitem['name'];
		}

		$this->menuitemType = $menuitem['type'];

		// Save information about edited item to the form
		$menuitem['editing'] = $id;

		$this->setDefaults($menuitem);
	}

	/**
	 * Adds 'module_view_argument' input. An input with possible paremeters for chosenView in chosenModule
	 */
	public function addModuleViewArgumentsInput($module, $moduleView)
	{
		$module_view_arguments = $this->moduleManager->getViewParameters($module, $moduleView);

		if (is_array($module_view_arguments))
			$this->addSelect('module_view_argument', 'with argument', $module_view_arguments);
		elseif (is_string($module_view_arguments))
			$this->addText('module_view_argument', 'with argument');
		else
			$this->addHidden('module_view_argument');
	}

	/**
	 * Sets given module as default in the form and changes items in module_view selectbox
	 * @param string $name Module name
	 */
	public function chooseModule($name)
	{
		$this->setDefaults(array('module_name' => $name));

		// Set possible views for this module
		$views = $this->moduleManager->getViews($name);
		$this['module_view']->setItems($views);
	}

	/* ----------------------- FORM CREATION AND SUBMIT --------------------- */

	public function setup()
	{
		$linkableModules = $this->moduleManager->getModulesNames();

		// Default values
		reset($linkableModules);
		$defaultModule = key($linkableModules);

		$defaultModuleViews = $this->moduleManager->getViews($defaultModule);

		$defaultView = array_keys($defaultModuleViews);
		$defaultView = array_shift($defaultView);

		// Form
		$this->addHidden('id');
		$this->addHidden('order');
		$this->addHidden('editing');

		$this->addRadioList('type', 'I want to add a', array(
			Menuitem::TYPE_MODULE => 'module link,',
			Menuitem::TYPE_SUBMENU => 'submenu,')
		);

		/* Option 1: Link to module (modulelink) */
		$this->addText('module_caption', 'titled');
		$this->addSelect('module_name', 'and linking to the module', $linkableModules);
		$this->addSelect('module_view', 'and it\'s view', $defaultModuleViews);
		$this->addModuleViewArgumentsInput($defaultModule, $defaultView);

		// In a submenu?
		$submenus = array(0 => 'No!');
		$storedsubmenus = $this->menuitems->fetchSubmenusPairs();
		if (is_array($storedsubmenus))
			$submenus = $submenus + $storedsubmenus;
		$this->addSelect('menuitem_id', 'Should it be in a submenu?', $submenus);

		$this->addCheckbox('strict_link_comparison', 'Strict link comparison *');

		/* Option 2: Submenu (submenulink) */
		$this->addText('submenu_caption', 'titled');

		/**/
		$this->addSubmit('save', 'Save');

		$this->setDefaults(array(
			'type' => $this->menuitemType,
			'module_name' => $defaultModule,
			'module_view' => $defaultView,
			'strict_link_comparison' => true
		));

		$this->onSuccess[] = array($this, 'menuitemSubmit');
	}

	public function menuitemSubmit($form)
	{
		// Get data from the form and prepare them
		$menuitem = $form->getValues();

		if ($menuitem['type'] === Menuitem::TYPE_MODULE)
			$menuitem['name'] = $menuitem['module_caption'];
		else
			$menuitem['name'] = $menuitem['submenu_caption'];

		unset($menuitem['submenu_caption']);
		unset($menuitem['module_caption']);

		if ($menuitem['menuitem_id'] === '0')
			$menuitem['menuitem_id'] = null;

		if (empty($menuitem['id']))
			$menuitem['id'] = null;
		if (empty($menuitem['order']))
			$menuitem['order'] = null;

		// If this isnt submitted by button, it is a request for a change of the form mode
		if (!$form['save']->isSubmittedBy()) {

			/* FORM CHANGE */

			// If input _editing_ isn't empty turn on editing mode
			if ($menuitem['editing'] !== '')
				$this->toggleEditingMode();

			// When request is from type modulelink form (in type submodule module_name is empty)
			if (!empty($menuitem['module_name'])) {

				// Set everything as it was
				$this->setDefaults($menuitem);

				// Sets module as default in form and changes items in module_view selectbox
				$this->chooseModule($menuitem['module_name']);

				if (!in_array($menuitem['module_view'], array_keys($this->moduleManager->getViews($menuitem['module_name'])))) {
					/* Condition passed if module was changed (selected view is NOT in array of views of current module)
					  (BUT condition won't be matched when two modules have the same view - but this won't make troubles) */

					// Select some default view
					$views = $this->moduleManager->getViews($menuitem['module_name']); // Possible views for this module
					$views_keys = array_keys($views);
					$menuitem['module_view'] = array_shift($views_keys);
				}

				// Set default module_view in form
				$this->setDefaults(array('module_view' => $menuitem['module_view']));

				// Renew view's parameters input
				unset($this['module_view_argument']);
				$this->addModuleViewArgumentsInput($menuitem['module_name'], $menuitem['module_view']);
			} else {
				$this['strict_link_comparison']->setValue(true); // set default checked
			}

			// Preserve type
			$this->menuitemType = $menuitem['type'];

			// Invalidate control
			$this->parent->invalidateControl('MenuitemFormSnippet');
		} else {

			/* SAVING */

			unset($menuitem['editing']);

			try {
				$id = $menuitem['id'];
				$this->menuitems->save($menuitem, 'id');

				if ($id !== null)
					$this->parent->flashMessage('Item changed');
				else
					$this->parent->flashMessage('Item added');
			} catch (Exception $e) {
				$this->parent->flashMessage('Something went wrong, please try again');
			}

			$this->parent->redirect('default');
		}
	}

	/* -------------------------- GETTERS, SETTERS -------------------------- */

	public function setMenuitemType($type)
	{
		if ($type === Menuitem::TYPE_MODULE || $type === Menuitem::TYPE_SUBMENU)
			$this->menuitemType = $type;
		else
			throw new \Nette\InvalidArgumentException('Invalid argument supplied for ' . get_class($this) . '::setMenuitemType.');

		$this->setDefaults(array('type' => $this->menuitemType));
	}

	public function getMenuitemType()
	{
		return $this->menuitemType;
	}

	public function getEditingMode()
	{
		return $this->editingMode;
	}

}
