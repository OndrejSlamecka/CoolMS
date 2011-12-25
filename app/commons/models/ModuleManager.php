<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Application;

class ModuleManager extends \Nette\Object
{

    /** @var \Nette\DI\Container */
    private $container;

    /** @var \Nette\Caching\Cache */
    private $cache;

    /* ----------------------- CONSTRUCTOR, DESIGN -------------------------- */

    public function __construct(\Nette\DI\Container $container)
    {
        $this->container = $container;

        $linkableModules = $this->getLinkableModules();
        if ($linkableModules === null) {
            $this->buildLinkableModulesCache();
        }

        $modulesNames = $this->getModulesInfo();
        if ($modulesNames === null) {
            $this->buildModulesInfoCache();
        }
    }

    /**
     * @return Nette\Caching\Cache
     */
    private function getCache()
    {
        if ($this->cache === null)
            $this->cache = new \Nette\Caching\Cache($this->container->cacheStorage, 'ModulesCache');

        return $this->cache;
    }

    /* ----------------------------- METHODS -------------------------------- */

    /**
     * Returns views of given module
     * @param string $name
     * @return array
     */
    public function getModuleViews($name)
    {
        $modules = $this->getModulesInfo();
        return $modules[$name]['methods'];
    }

    /**
     * Calls get<$view>ViewPossibleParams from given module
     * @param string $module
     * @param string $view
     * @return mixed
     */
    public function getModuleViewParams($module, $view)
    {
        $presenter_name = "{$module}Module\\FrontendPresenter";
        $presenter = new $presenter_name($this->container);

        $method_name = 'get' . ucfirst($view) . 'ViewPossibleParams';
        return $presenter->$method_name();
    }

    public function getLinkableModules()
    {
        return $this->getCache()->load('linkableModules');
    }

    /**
     * Returns array. Key is an actual name of a presenter and value is its formal name
     * @return array
     */
    public function buildLinkableModulesCache()
    {
        $presenters = $this->container->getService('presenterTree');
        $presenters = $presenters->getPresenters();

        $links = array();
        foreach ($presenters as $presenter) {
            if ($presenter->getPresenterReflection()->hasAnnotation('module')) {
                $links[$presenter->module] = $presenter->getPresenterReflection()->getAnnotation('module')->name;
            }
        }
        $this->getCache()->save('linkableModules', $links);
    }

    public function getModulesInfo()
    {
        return $this->getCache()->load('modulesNames');
    }

    /**
     * Returns array. Key is name, value is array with key 'name' (formal name) and 'methods' - again array name=>formal_name
     */
    public function buildModulesInfoCache()
    {
        $modules = $this->getLinkableModules();
        $modules = array_keys($modules);

        $modules_names = array();

        foreach ($modules as $module) {
            $moduleFront = $module . 'Module\\FrontendPresenter';
            $moduleFront = new $moduleFront($this->container);
            $moduleFrontReflection = $moduleFront->getReflection();
            $modules_names[$module]['name'] = $moduleFrontReflection->getAnnotation('module')->name;

            $methods = get_class_methods($moduleFront);
            $modules_names[$module]['methods'] = array();

            foreach ($methods as $method) {
                $methodReflection = $moduleFrontReflection->getMethod($method);

                $method = \Nette\Utils\Strings::replace($method, '~^render~');
                $method = lcfirst($method);

                if ($methodReflection->hasAnnotation('view'))
                    $modules_names[$module]['methods'][$method] = $methodReflection->getAnnotation('view')->name;
            }
        }

        $this->getCache()->save('modulesNames', $modules_names);
    }

}