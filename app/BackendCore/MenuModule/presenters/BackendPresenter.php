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

use Nette\Environment;
use App\Entity\Menuitem;
use App\Repository\Menuitems;
use App\Repository\Pages;

/**
 * Menu manager
 *
 * @author Ondrej Slamecka
 */
class BackendPresenter extends \Backend\BasePresenter
{
    /* STARTUP */

    public function startup()
    {
        parent::startup();
        if (!isset($this->template->editing))
            $this->template->editing = false;

        if (!isset($this->template->menuitemtype))
            $this->template->menuitemtype = Menuitem::TYPE_MODULE;

        $module = $this->getSession('modules');
        $moduleManager = $this->context->moduleManager;

        if (!isset($module->chosen)) {
            $m = $moduleManager->getLinkableModules();
            reset($m);
            $module->chosen = key($m);
        }

        if (!isset($module->chosenView)) {
            $views = $moduleManager->getModuleViews($module->chosen);
            $views = array_keys($views);
            $module->chosenView = array_shift($views);
        }
    }

    /* ACTIONS */

    public function actionDelete($id)
    {
        $menuitems = $this->repositories->Menuitem;

        $item = $menuitems->find(array('id' => $id))->fetch();

        if (!$item) {
            $this->flashMessage('Item was not found');
            $this->redirect('default');
        }

        try {
            $menuitems->remove(array('id' => $id));
            $this->flashMessage('Item removed');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    /* HANDLES */

    public function handleEdit($id)
    {
        $menuitems = $this->repositories->Menuitem;

        $this->template->editing = true;
        $menuitem = $menuitems->find(array('id' => $id))->fetch();

        if ($menuitem['type'] === Menuitem::TYPE_MODULE) {
            $menuitem['module_caption'] = $menuitem['name'];

            $module = $this->getSession('modules');
            $module->chosen = $menuitem['module_name'];
            $module->chosenView = $menuitem['module_view'];
        } else {
            $menuitem['submenu_caption'] = $menuitem['name'];
            $this->template->menuitemtype = Menuitem::TYPE_SUBMENU;
        }

        $this['menuitemForm']->setDefaults($menuitem);

        $this->invalidateControl('MenuitemFormSnippet');
    }

    /* Dependency: function name at docroot/admintheme/js/main.js */

    public function handleChangeFormMenuitemType($type)
    {
        $this->template->menuitemtype = $type;
        $this->invalidateControl('MenuitemFormSnippet');
    }

    public function handleChangeFormChooseModule($name)
    {
        $moduleManager = $this->context->moduleManager;
        $module = $this->getSession('modules');

        $module->chosen = $name;

        $views = $moduleManager->getModuleViews($module->chosen);
        $views = array_keys($views);
        $module->chosenView = array_shift($views);

        $this->invalidateControl('MenuitemFormSnippet');
    }

    public function handleChangeFormChooseModuleView($name)
    {
        $module = $this->getSession('modules');
        $module->chosenView = $name;
        $this->invalidateControl('MenuitemFormSnippet');
    }

    /* RENDERING */

    public function renderDefault()
    {
        $menu = $this->repositories->Menuitem;
        $this->template->menuitems = $menu->fetchStructured();

        $menu->fixOrder();
    }

    /* MENU DESIGNER CONTROL - STRUCTURE */

    /* menuDesignerControlForm */

    public function createComponentMenuDesignerControlForm($name)
    {
        $form = new \App\Form($this, $name);

        $form->addHidden('structure');
        $form->addSubmit('save', 'Save menu order');

        $form->onSuccess[] = array($this, 'menudesignerSubmit');

        return $form;
    }

    public function menudesignerSubmit($form)
    {
        /* TODO: This method is relict from old version: REWORK, its ugly */

        $structure = $form->getValues();
        $structure = $structure['structure'];

        $structure = json_decode($structure, true);


        $newOrder = array();
        $childrenParents = array();


        $i = 1;
        foreach ($structure as $p_id => $children) {
            $p_id = (int) preg_replace("~^mi-([0-9]+)~", "$1", $p_id);
            $newOrder[$p_id] = $i;

            $j = 1;
            foreach ($children as $ch_id => $null) {
                $ch_id = (int) preg_replace("~^mi-([0-9]+)~", "$1", $ch_id);
                $newOrder[$ch_id] = $j;
                $childrenParents[$ch_id] = $p_id;
                $j++;
            }

            $i++;
        }

        $menuitems = $this->repositories->Menuitem;

        try {

            $menuitems->orderUpdate($newOrder);

            $menuitems->parentsUpdate($childrenParents);

            $menuitems->cleanCache();

            $this->flashMessage('Changes saved');
        } catch (Exception $e) {
            $this->flashMessage('Saving changes was not successful, please try again');
        }

        $this->redirect('default');
    }

    /* MENU ITEM FORM */

    /* Menu items */

    public function createComponentMenuitemForm($name)
    {
        $form = new \App\Form($this, $name);
        $menu = $this->repositories->Menuitem;


        $form->addHidden('id');
        $form->addHidden('order');

        if ($this->template->editing) {
            $labels = array(
                'type' => 'Link is a',
                'module_name' => 'to module',
                'module_view' => 'and it\'s view',
                'module_view_param' => 'with parameter',
                'module_caption' => 'It is titled',
                'submenu_caption' => 'titled',
                'parent' => 'In submenu?',
                'strict_link_comparison' => 'Strict link comparison *',
                'save' => 'Save'
            );
        } else {
            $labels = array(
                'type' => 'I want to add a',
                'module_name' => 'to module',
                'module_view' => 'and it\'s view',
                'module_view_param' => 'with parameter',
                'module_caption' => 'And titled',
                'submenu_caption' => 'titled',
                'parent' => 'In submenu?',
                'strict_link_comparison' => 'Strict link comparison *',
                'save' => 'Save'
            );
        }

        $form->addRadioList('type', $labels['type'], array(
            Menuitem::TYPE_MODULE => 'module link,',
            Menuitem::TYPE_SUBMENU => 'submenu,')
        );


        $module = $this->getSession('modules');

        $moduleManager = $this->context->moduleManager;

        /* Option 1: Link to module (modulelink) */
        $form->addSelect('module_name', $labels['module_name'], $moduleManager->getLinkableModules());
        $form->addSelect('module_view', $labels['module_view'], $moduleManager->getModuleViews($module->chosen));

        $module_view_params = $moduleManager->getModuleViewParams($module->chosen, $module->chosenView);
        if (is_array($module_view_params))
            $form->addSelect('module_view_param', $labels['module_view_param'], $module_view_params);
        elseif (is_string($module_view_params))
            $form->addText('module_view_param', $labels['module_view_param']);
        else
            $form->addHidden('module_view_param');

        $form->addText('module_caption', $labels['module_caption']);

        /* Option 2: Submenu (submenulink) */
        $form->addText('submenu_caption', $labels['submenu_caption']);


        /**/
        $submenus = array(0 => 'No!');
        $storedsubmenus = $menu->fetchSubmenusPairs();
        if (is_array($storedsubmenus))
            $submenus = $submenus + $storedsubmenus;

        $form->addSelect('parent', $labels['parent'], $submenus);

        $form->addCheckbox('strict_link_comparison', $labels['strict_link_comparison']);

        /**/
        $form->addSubmit('save', $labels['save']);

        $form->setDefaults(array(
            'type' => $this->template->menuitemtype,
            'module_name' => $module->chosen,
            'module_view' => $module->chosenView,
            'strict_link_comparison' => true
        ));

        $form->onSuccess[] = array($this, 'menuitemSubmit');

        return $form;
    }

    public function menuitemSubmit($form)
    {
        $menuitem = $form->getValues();
        $menu = $this->repositories->Menuitem;

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
            $menu->save($menuitem, 'id');

            if ($menuitem['id'] !== null)
                $this->flashMessage('Item changed');
            else
                $this->flashMessage('Item added');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }

        $this->redirect('default');
    }

}
