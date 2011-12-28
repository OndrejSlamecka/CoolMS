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
     * Returns array. Key is an actual name of a presenter and value is its public name
     * @return array
     */
    public function buildLinkableModulesCache()
    {
        $presenterTree = $this->container->getService('presenterTree');
        $modules = $presenterTree->getModules();

        $links = array();
        foreach ($modules as $module) {
            $presenter_name = $module . 'Module\\FrontendPresenter';
            if (class_exists($presenter_name)) {
                $reflection = new \Nette\Reflection\ClassType($presenter_name);

                if ($reflection->hasAnnotation('module'))
                    $links[$module] = $reflection->getAnnotation('module')->name;
            }
        }
        $this->getCache()->save('linkableModules', $links);
    }

    public function getModulesInfo()
    {
        return $this->getCache()->load('modulesNames');
    }

    /**
     * Returns array. Key is name, value is array with key 'name' (public name) and 'methods' - again array name=>public_name
     */
    public function buildModulesInfoCache()
    {
        $modules = $this->getLinkableModules();
        $modules_names = array();

        foreach ($modules as $module => $public_name) {

            // Write down basic information
            $modules_names[$module] = array();
            $modules_names[$module]['name'] = $public_name;

            // Get presenter reflection
            $presenter_name = $module . 'Module\\FrontendPresenter';
            $presenter_reflection = new \Nette\Reflection\ClassType($presenter_name);

            // Get methods of reflected class
            $methods = get_class_methods($presenter_name);
            $modules_names[$module]['methods'] = array();

            // Try every method if is a module's view
            foreach ($methods as $method) {
                $method_reflection = $presenter_reflection->getMethod($method);

                // If method has 'view' annotation with parameter name
                if ($method_reflection->hasAnnotation('view') && ($method_public_name = $method_reflection->getAnnotation('view')->name)) {
                    $method = \Nette\Utils\Strings::replace($method, '~^render~');
                    $method = lcfirst($method);

                    $modules_names[$module]['methods'][$method] = $method_public_name;
                }
            }
        }

        $this->getCache()->save('modulesNames', $modules_names);
    }

}