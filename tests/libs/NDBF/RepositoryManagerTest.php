<?php

class RepositoryManagerTest extends PHPUnit_Framework_TestCase
{  
    
    public function testGetRepository()
    {
        $rm = new \NDBF\RepositoryManager( \Nette\Environment::getContext() );                        
        self::assertInternalType( 'object', $rm->getRepository( 'FooBarTestRepository' ) );        
    }
 
    /**
     * @depends testGetRepository
     */
    public function test__get()
    {
        $rm = new \NDBF\RepositoryManager( \Nette\Environment::getContext() );                        
        self::assertInternalType( 'object', $rm->FooBarTestRepository );        
    }
    
}