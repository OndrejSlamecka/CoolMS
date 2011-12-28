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
            $menuitems->remove(array('id' => $id));
            $this->flashMessage('Item removed');
        } catch (Exception $e) {
            $this->flashMessage('Something went wrong, please try again');
        }
        $this->redirect('default');
    }

    public function renderDefault()
    {
        $menu = $this->repositories->Menuitem;
        $this->template->menuitems = $menu->fetchStructured();
    }

    /* --------------------------- MENU ITEM FORM --------------------------- */

    public function handleEdit($id)
    {
        $this['menuitemForm']->toggleEditing($id);
        $this->invalidateControl('MenuitemFormSnippet');
    }

    /* Dependency: function name at docroot/admintheme/js/main.js */

    public function handleChangeFormMenuitemType($type)
    {
        $this['menuitemForm']->menuitemType = $type;
        $this->invalidateControl('MenuitemFormSnippet');
    }

    public function handleChangeFormChooseModule($name)
    {
        $this['menuitemForm']->chosenModule = $name;
        $this->invalidateControl('MenuitemFormSnippet');
    }

    public function handleChangeFormChooseModuleView($name)
    {
        $this['menuitemForm']->chosenModuleView = $name;
        $this->invalidateControl('MenuitemFormSnippet');
    }

    public function createComponentMenuitemForm($name)
    {
        $session = $this->getSession('MenuitemForm');
        $moduleManager = $this->getService('moduleManager');
        $menuitemRepository = $this->getService('repositoryManager')->Menuitem;
        return new MenuitemForm($this, $session, $moduleManager, $menuitemRepository);
    }
    
    /**
     * Called from MenuitemForm
     * @param type $message 
     */
    public function menuitemFormSubmit($message)
    {
        $this->flashMessage($message);
        $this->redirect('default');
    }

    /* ----------------- MENU DESIGNER CONTROL (STRUCTURE) ------------------ */

    public function createComponentDesignerControlForm($name)
    {
        return new DesignerForm($this, $name, $this->getService('repositoryManager')->Menuitem);
    }

    /**
     * Called from DesignerForm
     * @param type $message 
     */
    public function designerFormSubmit($message)
    {
        $this->flashMessage($message);
        $this->redirect('default');
    }

}
