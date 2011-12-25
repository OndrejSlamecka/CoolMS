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
 * @module(name="Pages")
 */
class FrontendPresenter extends \Frontend\BasePresenter
{

    /**
     * @view(name="Detail")
     */
    public function renderDefault($name)
    {
        $pages = $this->repositories->Page;
        $this->template->page = $pages->find(array('name_webalized' => $name))->fetch();


        /* Set template and prepare it if page has it's own */
        if ($this->template->page['template'] !== null
                && file_exists($this->getTemplatesFolder() . '/templates/' . $this->template->page['template'] . '.latte')) {
            $method = 'prepare' . ucfirst($this->template->page['template']) . 'Template';
            if (method_exists($this, $method))
                $this->$method();

            $this->setView('templates/' . $this->template->page['template']);
        }
    }

    public function prepareContactTemplate()
    {
        
    }

    public function getDefaultViewPossibleParams()
    {
        $pages = $this->repositories->Page;
        $pages = $pages->find()->fetchPairs('name_webalized', 'name');

        foreach ($pages as $nw => $n) {
            unset($pages[$nw]);
            $pages['name=' . $nw] = $n;
        }

        return $pages;
    }

}
