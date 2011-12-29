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

use \Application\Entity\Menuitem,
    \Nette\ComponentModel\IContainer,
    \Nette\Http\SessionSection;

class MenuitemForm extends \Application\Form
{

    /** @var \Nette\ComponentModel\IContainer */
    private $parent;

    /** @var \Nette\Http\SessionSection */
    private $session;

    /** @var \Application\ModuleManager */
    private $moduleManager;

    /** @var \Application\Repository\Menuitem */
    private $menuitems;

    /**/
    private $editingMode;
    private $menuitemType;

    /**
     * @param \Nette\ComponentModel\IContainer
     * @param string
     */
    public function __construct(IContainer $parent, SessionSection $session, \Application\ModuleManager $moduleManager, \Application\Repository\Menuitem $menuitemRepository)
    {
        $this->parent = $parent;
        $this->session = $session;
        $this->moduleManager = $moduleManager;
        $this->menuitems = $menuitemRepository;

        $this->menuitemType = Menuitem::TYPE_MODULE;

        /* Set default values to module preferences */
        if (empty($this->chosenModule)) {
            $m = $moduleManager->getLinkableModules();
            reset($m);
            $this->setChosenModule(key($m), FALSE);
        }

        if (empty($this->chosenModuleView)) {
            $views = $moduleManager->getModuleViews($this->chosenModule);
            $views = array_keys($views);
            $this->setChosenModuleView(array_shift($views), FALSE);
        }

        parent::__construct($parent, 'menuitemForm'); // calls setup       
    }

    /* --------------------------- GENERAL METHODS -------------------------- */

    /**
     * Turns the form into editing mode
     * @param int $id Item id 
     */
    public function toggleEditing($id)
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
            'parent' => 'Should it be in a submenu?',
            'strict_link_comparison' => 'Strict link comparison *',
            'save' => 'Save'
        );

        foreach ($labels as $control => $label) {
            $this[$control]->caption = $label;
        }

        /* Settings specifing for the item being edited item */
        $menuitem = $this->menuitems->find(array('id' => $id))->fetch();

        if ($menuitem['type'] === Menuitem::TYPE_MODULE) {
            $menuitem['module_caption'] = $menuitem['name'];

            $this->setChosenModule($menuitem['module_name'], FALSE);
            $this->chosenModuleView = $menuitem['module_view'];
        } else {
            $menuitem['submenu_caption'] = $menuitem['name'];
            $this->menuitemType = Menuitem::TYPE_SUBMENU;
        }

        $this->setDefaults($menuitem);
    }

    /**
     * Adds 'module_view_argument' input. An input with possible paremeters for chosenView in chosenModule
     */
    public function addModuleViewParametersInput()
    {
        $module_view_arguments = $this->moduleManager->getModuleViewParams($this->chosenModule, $this->chosenModuleView);
        if (is_array($module_view_arguments))
            $this->addSelect('module_view_argument', 'with argument', $module_view_arguments);
        elseif (is_string($module_view_arguments))
            $this->addText('module_view_argument', 'with argument');
        else
            $this->addHidden('module_view_argument');
    }

    /* ----------------------- FORM CREATION AND SUBMIT --------------------- */

    public function setup()
    {
        $this->addHidden('id');
        $this->addHidden('order');

        $this->addRadioList('type', 'I want to add a', array(
            Menuitem::TYPE_MODULE => 'module link,',
            Menuitem::TYPE_SUBMENU => 'submenu,')
        );

        /* Option 1: Link to module (modulelink) */
        $this->addText('module_caption', 'titled');
        $this->addSelect('module_name', 'and linking to the module', $this->moduleManager->getLinkableModules());
        $this->addSelect('module_view', 'and it\'s view', $this->moduleManager->getModuleViews($this->chosenModule));
        $this->addModuleViewParametersInput();

        // In submenu?
        $submenus = array(0 => 'No!');
        $storedsubmenus = $this->menuitems->fetchSubmenusPairs();
        if (is_array($storedsubmenus))
            $submenus = $submenus + $storedsubmenus;
        $this->addSelect('parent', 'Should it be in a submenu?', $submenus);

        $this->addCheckbox('strict_link_comparison', 'Strict link comparison *');

        /* Option 2: Submenu (submenulink) */
        $this->addText('submenu_caption', 'titled');

        /**/
        $this->addSubmit('save', 'Save');

        $this->setDefaults(array(
            'type' => $this->menuitemType,
            'module_name' => $this->chosenModule,
            'module_view' => $this->chosenModuleView,
            'strict_link_comparison' => true
        ));

        $this->onSuccess[] = array($this, 'menuitemSubmit');
    }

    public function menuitemSubmit($form)
    {
        $menuitem = $form->getValues();

        if ($menuitem['type'] === Menuitem::TYPE_MODULE) {
            $menuitem['name'] = $menuitem['module_caption'];
        }else
            $menuitem['name'] = $menuitem['submenu_caption'];

        unset($menuitem['submenu_caption']);
        unset($menuitem['module_caption']);

        if ($menuitem['parent'] === '0')
            $menuitem['parent'] = null;

        if (empty($menuitem['id']))
            $menuitem['id'] = null;
        if (empty($menuitem['order']))
            $menuitem['order'] = null;

        try {
            $this->menuitems->save($menuitem, 'id');

            if ($menuitem['id'] !== null)
                $this->parent->menuitemFormSubmit('Item changed');
            else
                $this->parent->menuitemFormSubmit('Item added');
        } catch (Exception $e) {
            $this->parent->menuitemFormSubmit('Something went wrong, please try again');
        }

        $this->parent->redirect('default');
    }

    /* -------------------------- GETTERS, SETTERS -------------------------- */

    /**
     * Changes chosenModule and corresponding inputs (if were defined)
     * @param string $name
     * @param bool $presetView Set default module_view?
     */
    public function setChosenModule($name, $presetView = TRUE)
    {
        $this->session->chosenModule = $name;

        if ((isset($this['module_name']) && isset($this['module_view'])) || $presetView)
            $views = $this->moduleManager->getModuleViews($name); // Possible views for this module

        if (isset($this['module_name']) && isset($this['module_view'])) {
            $this->setDefaults(array('module_name' => $name));

            // Set possible views to their input
            $this['module_view']->setItems($views);
        }

        if ($presetView) {
            // Default chosen view
            $views_keys = array_keys($views);
            $this->chosenModuleView = array_shift($views_keys);
        }
    }

    /**
     * @return string
     */
    public function getChosenModule()
    {
        return $this->session->chosenModule;
    }

    /**
     * Changes chosenModuleView and corresponding inputs (if were defined)
     * @param string $name
     * @param bool $presetArgument Set default module_view?
     */
    public function setChosenModuleView($name, $presetArgument = TRUE)
    {
        $this->session->chosenModuleView = $name;

        if (isset($this['module_view']))
            $this->setDefaults(array('module_view' => $name));

        if ($presetArgument) {
            // Renew view's parameters input
            unset($this['module_view_argument']);
            $this->addModuleViewParametersInput();
        }
    }

    /**
     * @return string
     */
    public function getChosenModuleView()
    {
        return $this->session->chosenModuleView;
    }

    public function setMenuitemType($type)
    {
        if ($type === Menuitem::TYPE_MODULE)
            $this->menuitemType = $type;
        elseif ($type === Menuitem::TYPE_SUBMENU)
            $this->menuitemType = $type;
        else
            throw new \Nette\InvalidArgumentException('Invalid argument supplied to ' . get_class($this) . '::setMenuitemType.');

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
