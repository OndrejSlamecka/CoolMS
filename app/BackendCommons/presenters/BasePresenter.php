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
 * Base class for all admin module presenters.
 *
 * @author     Ondrej Slamecka
 */
abstract class BasePresenter extends \BasePresenter
{

    public function createTemplate($class = NULL)
    {
        $template = parent::createTemplate($class);
        $template->loggedUser = $this->context->user;
        $template->themePath = $template->basePath . '/admintheme';

        return $template;
    }

    public function startup()
    {
        parent::startup();

        // If user isn't signed in, redirects to AuthenticationPresenter (more restrictions will be solved there)
        if (!$this->getUser()->isLoggedIn()) {
            if ($this->getName() !== 'Authentication:Backend') {
                $this->redirect(':Authentication:Backend:login');
            }
        } else {
            if ($this->getName() === 'Authentication:Backend' && $this->getAction() !== 'logout') {
                $this->redirect(':Dashboard:Backend:');
            }
        }

        $this->setLayout($this->context->params['appDir'] . '/BackendCommons/templates/@layout.latte');
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $modules = $this->context->moduleManager;
        $this->template->modules = $modules->getLinkableModules();
    }

}
