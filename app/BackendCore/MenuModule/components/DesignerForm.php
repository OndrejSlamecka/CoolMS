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

class DesignerForm extends \Application\Form
{

    /** @var \Nette\ComponentModel\IContainer */
    private $parent;

    /** @var \Application\Repository\Menuitem */
    private $menuitems;

    /**
     * @param \Nette\ComponentModel\IContainer
     * @param string
     */
    public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Application\Repository\Menuitem $menuitemRepository)
    {
        $this->parent = $parent;
        $this->menuitems = $menuitemRepository;
        parent::__construct($parent, $name);
    }

    public function setup()
    {
        $this->addHidden('structure');
        $this->addSubmit('save', 'Save menu order');

        $this->onSuccess[] = array($this, 'designerSubmit');
    }

    public function designerSubmit($form)
    {
        $structure = $form->getValues();
        $structure = $structure['structure'];

        $structure = json_decode($structure, true);

        $newOrder = array();
        $childrenParents = array();

        // TODO: Wouldn't it be better to iterate using for?
        $i = 1;
        foreach ($structure as $p_id => $children) {
            $p_id = (int) \Nette\Utils\Strings::replace($p_id, "~^mi-([0-9]+)~", "$1");
            $newOrder[$p_id] = $i;

            $j = 1;
            foreach ($children as $ch_id => $null) {
                $ch_id = (int) \Nette\Utils\Strings::replace($ch_id, "~^mi-([0-9]+)~", "$1");
                $newOrder[$ch_id] = $j;
                $childrenParents[$ch_id] = $p_id;
                $j++;
            }

            $i++;
        }

        try {
            $this->menuitems->orderUpdate($newOrder);
            $this->menuitems->parentsUpdate($childrenParents);

            $this->menuitems->cleanCache();

            $this->parent->designerFormSubmit('Changes saved');
        } catch (Exception $e) {
            $this->parent->designerFormSubmit('Saving changes was not successful, please try again');
        }
    }

}
