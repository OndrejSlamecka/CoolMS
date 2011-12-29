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

use Nette\Application\Routers\Route,
    Nette\Utils\Strings;

/**
 * Front module routing. TODO: Hard refactoring…
 * 
 * @author Ondrej Slamecka
 */
class RouteManager extends \Nette\Object
{

    /** @var Nette\Caching\Cache */
    private $cache;

    /** @var Nette\Caching\IStorage */
    private $cacheStorage;

    /** @var Application\ModuleManager */
    private $moduleManager;

    /** @var Application\Repository\Menuitem */
    private $menu;

    function __construct($container)
    {
        $this->menu = $container->repositoryManager->Menuitem;
        $this->cacheStorage = $container->cacheStorage;
        $this->moduleManager = $container->moduleManager;

        $translationTable = $this->getCache()->load('translationTable');
        if ($translationTable === null) {
            $this->buildTranslationTableCache();
        }
    }

    /**
     *
     * @param Nette\Application\Routers\RouteList $router 
     */
    public function addRoutes(&$router)
    {
        $names = $this->getTranslationTable();
        $modules = array_flip($names['names']);

        /* FRONT ROUTES ARE EDITED HERE */

        // Index
        $router[] = new Route('', $this->getIndexMetadata());

        // Module: Page
        $router[] = new Route($modules['Page'] . '/<name>',
                        $this->formMetadata('Page', 'default')
        );

        // Module: Article
        $articleMethods = array_flip($names['methods']['Article']);

        $router[] = new Route($modules['Article'],
                        $this->formMetadata('Article', 'default')
        );

        // webalize so that cool-uri will appear, not any unfriendly characters
        $router[] = new Route($modules['Article'] . '/' . Strings::webalize($articleMethods['archive']),
                        $this->formMetadata('Article', 'archive')
        );

        $router[] = new Route($modules['Article'] . '/<name>',
                        $this->formMetadata('Article', 'detail')
        );

        // The rest...
        $router[] = new Route('<module>/<action>[/<name>]',
                        $this->getIndexMetadata()
        );
    }

    /* METHODS */

    /**
     * Forms metadata into form accepted by Nette\Application\Routers\Route. Adds translation tables
     * @param string $module
     * @param string $action
     * @param array $args
     * @return array 
     */
    public function formMetadata($module, $action = null, $args = null)
    {
        $transl_table = $this->getTranslationTable();

        $metadata = array('presenter' => 'Frontend',
            'module' => array(
                Route::VALUE => $module,
                Route::FILTER_TABLE => $transl_table['names'],
            )
        );

        if ($action !== null) {
            /* // Note to myself: 
             * // Remove later when ensured that following 3 lines are wrong/useless/unnecessary
             * $methods = array();
             * foreach($transl_table['methods'][$module] as $key => $method)
             *     $methods[ $key ] = \Nette\Utils\Strings::webalize($method);   
             *
             */
            $metadata += array('action' => array(
                    Route::VALUE => $action,
                    Route::FILTER_TABLE => $transl_table['methods'][$module]
                )
            );
        }

        if (is_array($args))
            $metadata += $args;

        return $metadata;
    }

    public function getTranslationTable()
    {
        return $this->getCache()->load('translationTable');
    }

    public function buildTranslationTableCache()
    {
        $moduleManager = $this->moduleManager;
        $names = $moduleManager->getModulesInfo();

        $names_filters = array();
        foreach ($names as $name => $module) {
            $names_filters['names'][strtolower($module['name'])] = $name;

            $names_filters['methods'][$name] = array();
            foreach ($module['methods'] as $method_name => $method_name_translated) {
                $names_filters['methods'][$name][strtolower($method_name_translated)] = $method_name;
            }
        }

        $this->getCache()->save('translationTable', $names_filters);
    }

    /* Index data */

    public function getIndexViewParams()
    {
        $index = $this->menu->getIndex();

        if (empty($index['module_view_argument']))
            return;

        $viewParams = $index['module_view_argument'];

        if (!empty($viewParams)) {
            $paramsPairs = explode(';', $viewParams);
            $viewParams = array();
            foreach ($paramsPairs as $pair) {
                $pair = explode('=', $pair);
                $viewParams[$pair[0]] = $pair[1];
            }
        }

        return $viewParams;
    }

    public function getIndexMetadata()
    {
        $index = $this->menu->getIndex();

        $transl_table = $this->getTranslationTable();

        $presenter = array(
            Route::VALUE => $index['module_name'],
            Route::FILTER_TABLE => $transl_table['names']
        );

        $front_default = array(
            'presenter' => 'Frontend',
            'module' => $presenter,
            'action' => $index['module_view']
        );

        $front_default += (array) $this->getIndexViewParams(); // (array) because of possible null value

        return $front_default;
    }

    /* ------------- */

    /**
     * @return Nette\Caching\Cache
     */
    private function getCache()
    {
        if ($this->cache === null)
            $this->cache = new \Nette\Caching\Cache($this->cacheStorage, 'FrontRoutesCache');

        return $this->cache;
    }

}
