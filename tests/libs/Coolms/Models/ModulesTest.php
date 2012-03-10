<?php

/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */
class ModulesTest extends PHPUnit_Framework_TestCase
{

    private $instance;

    public function setUp()
    {
        parent::setUp();
        $container = \Nette\Environment::getContext();
        $this->instance = $container->getService('coolms.modules');
    }

    /**
     * THIS TEST IS ONLY FOR DEFAULT INSTALLATION (else it would be too much mocking... todo in future)
     */
    public function testGetModulesNames()
    {
        $modules = $this->instance->getModulesNames();

        // Is first key Article?
        $keys = array_keys($modules);
        self::assertEquals('Article', array_shift($keys));

        // Is its value Articles (the translated name)?
        self::assertEquals($modules['Article'], 'Articles');
    }

    /**
     * @depends testGetLinkableModules
     */
    public function testGetViews()
    {
        $articleModuleViews = $this->instance->getViews('Article');

        // Does the array have key 'default'?
        self::assertTrue(array_key_exists('default', $articleModuleViews));

        // Is the value of key 'default' 'List'?
        self::assertEquals('List', $articleModuleViews['default']);
    }

    /**
     * @depends testGetModuleViews
     */
    public function testGetModules()
    {
        $module_info = $this->instance->getModulesInfo();

        // Is Article array?
        $article = array_shift($module_info);
        self::assertInternalType('array', $article);

        // Is Article's translated name Articles?
        self::assertEquals($article['name'], 'Articles');

        // Does Article have key views?
        self::assertTrue(array_key_exists('views', $article));
        $views = $article['views'];

        // Is first method default => "List" ?
        $default = array_shift($views);
        self::assertEquals($default, 'List');
    }

    public function testGetViewParameters()
    {
        $viewparams = $this->instance->getViewParameters('Page', 'default');

        // Test if params for Article:default are array. (=Are there some pages?)
        self::assertInternalType('array', $viewparams);
    }

}