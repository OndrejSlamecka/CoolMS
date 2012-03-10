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

    public function handleWebalizeName($name)
    {
        $this->payload->name_webalized = \Nette\Utils\Strings::webalize($name);
        $this->terminate();
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this->setLayout($this->context->parameters['appDir'] . '/BackendCommons/templates/@wysiwyg_layout.latte');
    }

}
