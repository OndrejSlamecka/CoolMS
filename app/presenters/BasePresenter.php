<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

/**
 * Base class for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->registerHelperLoader(array('App\Helpers', 'loader'));
        return $template;
    }

    final public function getRepositories()
    {
        return $this->context->repositoryManager;
    }

    public function getTemplatesFolder()
    {
        $dir = dirname(dirname($this->getReflection()->getFileName()));
        $name = $this->getName();
        $presenter = substr($name, strrpos(':' . $name, ':'));

        return "$dir/templates/$presenter";
    }

}
