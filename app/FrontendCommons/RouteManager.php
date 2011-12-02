<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace FrontModule;

use Nette\Application\Routers\Route;

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

    /** @var App\ModuleManager */
    private $moduleManager;

    /** @var App\Repository\Menuitem */
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

    public function addRoutes(&$router)
    {
        $names = $this->getTranslationTable();
        $names = array_flip($names['names']);

        /**/

        // Index
        $router[] = new Route('', $this->getIndexMetadata());

        // Module: Page
        $router[] = new Route($names['Page'] . '/<name>',
                        $this->formMetadata('Page', 'default')
        );

        // Module: Article
        $router[] = new Route($names['Article'],
                        $this->formMetadata('Article', 'default')
        );

        $router[] = new Route($names['Article'] . '/<name>',
                        $this->formMetadata('Article', 'detail')
        );

        // The rest...
        $router[] = new Route('<presenter>/<action>[/<name>]',
                        $this->getIndexMetadata()
        );
    }

    /* METHODS */

    public function formMetadata($presenter, $action = null, $args = null)
    {
        $transl_table = $this->getTranslationTable();

        $metadata = array('presenter' => 'Frontend',
            'module' => array(
                Route::VALUE => $presenter,
                Route::FILTER_TABLE => $transl_table['names'],
            )
        );

        if ($action !== null)
            $metadata += array('action' => array(
                    Route::VALUE => $action,
                    Route::FILTER_TABLE => $transl_table['methods'][$presenter]
                )
            );

        //var_dump( $transl_table['methods'][$presenter] );

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

    /*     * ******* */

    // TODO: Remove, obsolete?
    public function getIndexModule()
    {
        $index = $this->menu->getIndex();
        return $index['module_name'];
    }

    public function getIndexViewParams()
    {
        $index = $this->menu->getIndex();

        if (empty($index['module_view_param']))
            return;

        $viewParams = $index['module_view_param'];

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

    /*     * ********* */

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
