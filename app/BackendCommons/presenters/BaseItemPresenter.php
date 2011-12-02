<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Backend;

/**
 * Base class for all admin module presenters, which present items (pages, articles).
 *
 * @author     Ondrej Slamecka
 */
abstract class BaseItemPresenter extends BasePresenter
{

    private $session = null;

    public function startup()
    {
        parent::startup();
        $this->session = \Nette\Environment::getSession($this->getName() . 'PresenterStorage');
    }

}
