<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

class ModuleManagerTest extends PHPUnit_Framework_TestCase
{

    private $instance;

    public function setUp()
    {
        parent::setUp();
        $container = \Nette\Environment::getContext();
        $this->instance = new App\ModuleManager($container);
    }

    /**
     * THIS TEST IS ONLY FOR DEFAULT INSTALLATION (else it would be too much mocking... todo in future)
     */
    public function testGetLinkableModules()
    {
        $modules = $this->instance->getLinkableModules();

        // Is first key Article?
        $keys = array_keys($modules);
        self::assertEquals(array_shift($keys), 'Article');

        // Is its value Articles (the translated name)?
        self::assertEquals($modules['Article'], 'Articles');
    }

    /**
     * @depends testGetLinkableModules
     */
    public function testGetModuleViews()
    {
        $articleModuleViews = $this->instance->getModuleViews('Article');

        // Does the array have key 'default'?
        self::assertTrue(array_key_exists('default', $articleModuleViews));

        // Is the value of key 'default' 'list'?
        self::assertEquals('list', $articleModuleViews['default']);
    }

    /**
     * @depends testGetModuleViews
     */
    public function testGetModulesInfo()
    {
        $module_info = $this->instance->getModulesInfo();

        // Is Article array?
        $article = array_shift($module_info);
        self::assertInternalType('array', $article);

        // Is Article's translated name Articles?
        self::assertEquals($article['name'], 'Articles');

        // Does Article have key methods?        
        self::assertTrue(array_key_exists('methods', $article));
        $methods = $article['methods'];

        // Is first method default => "list" ?
        $default = array_shift($methods);
        self::assertEquals($default, 'list');
    }

    public function testGetModuleViewParams()
    {
        $viewparams = $this->instance->getModuleViewParams('Page', 'default');

        // Test if params for Article:default are array. (=Are there some pages?)
        self::assertInternalType('array', $viewparams);
    }

}