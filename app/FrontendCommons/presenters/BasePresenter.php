<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Frontend;

/**
 * Base class for all front module presenters.
 *
 * @author     Ondrej Slamecka
 * @package    CoolMS
 */
abstract class BasePresenter extends \BasePresenter
{

    public function startup()
    {
        parent::startup();
        $this->setLayout($this->context->params['appDir'] . '/FrontendCommons/templates/@layout.latte');
    }

    public function templatePrepareFilters($template)
    {
        /** MENU * */
        $template->registerFilter($latte = new \Nette\Latte\Engine);
        $set = \Nette\Latte\Macros\MacroSet::install($latte->parser);
        $set->addMacro('menulink', 'echo $presenter->menulink(%node.word);');
        $set->addMacro('isMenulinkCurrent', 'if($presenter->isMenulinkCurrent(%node.word)):', 'endif');
    }

    public function beforeRender()
    {
        /** MENU * */
        $menu = $this->repositories->Menuitem;
        $this->template->topMenu = $menu->find(array('parent' => null,
            'type != ?' => \App\Entity\Menuitem::TYPE_SUBMENU,)
                , '`order` ASC');
    }

    /*     * ******************************* MENU ********************************* */

    public function menulink($mi)
    {
        $path = ':' . $mi['module_name'] . ":Frontend:" . $mi['module_view'];
        $aParams = array();
        if (!empty($mi['module_view_param'])) {
            $params = explode(';', $mi['module_view_param']);
            foreach ($params as $param) {
                $param = explode('=', $param);
                $aParams[$param[0]] = $param[1];
            }
        }
        return $this->link($path, $aParams);
    }

    public function isMenulinkCurrent($mi)
    {
        if ($mi['strict_link_comparison'])
            $this->menulink($mi);
        else
            $this->link($mi['module_name'] . ":*");

        return $this->getPresenter()->getLastCreatedRequestFlag('current');
    }

}
