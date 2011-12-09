<?php

// TODO: Add dependencies
class RepositoryTest extends PHPUnit_Framework_TestCase
{  
    private $instance;
    private $reflection;
    
    /** @var Nette\Database\Connection */
    private $database;
    
    private function getClassProperty( $name )
    {
        $property = $this->reflection->getProperty( $name );                    
        $property->setAccessible(true);
        return $property->getValue( $this->instance );  
    }
    
    public function setUp()
    {
        parent::setUp();
        $container = \Nette\Environment::getContext();                
        $this->database = $container->database;
        
        // Instance and reflection
        $this->instance = new \NDBF\Repository( $container, 'testtable' );                                
        $this->reflection = new \Nette\Reflection\ClassType( $this->instance );        

        // Truncate
        $table_name = $this->getClassProperty( 'table_name' );
        $this->database->exec( "TRUNCATE TABLE $table_name" );
    }
    
    public function test__constructor()
    {                
        self::assertInternalType( 'string', $this->getClassProperty( 'table_name' ) );
        self::assertFalse( (bool) preg_match('~[A-Z]~', $this->getClassProperty( 'table_name' ) ) );
    }
    
    public function testGetDb()
    {
        $connection = $this->getClassProperty('connection');         
        
        self::assertInstanceOf( 'Nette\Database\Connection', $connection );
    }
    
    public function testTable()
    {        
        self::assertInstanceOf( 'Nette\Database\Table\Selection', $this->instance->table( $this->getClassProperty( 'table_name' ) ) );        
    }
    
    public function testSave()
    {               
        /* Inserting, returning received id in record, updating */
        $row = array();
        
        // Double save same record (same id - after first save written into record). One insert, one update
        $this->instance->save($row, 'id');
        $this->instance->save($row, 'id');
        
        // Expected: 1 item in db, id given to row
        $this->assertEquals(1, $this->database->table($this->getClassProperty( 'table_name' ))->count()  );
        //$this->assertEquals(1, $row['id'] ); // This assertion would be obsolete (if no id was given, two records would be present)
             

        /* Re-inserting deleted items */        
        $row = array( 'id' => 2 );
        
        $this->database->exec('INSERT INTO '.$this->getClassProperty( 'table_name' ) , $row);
        
        // In row id is 2, remove that record
        $this->database->exec( 'DELETE FROM '.$this->getClassProperty( 'table_name' ).' WHERE id=?', $row['id'] );
        
        $this->instance->save( $row, 'id' ); // Re-insert of deleted item
        
        $this->assertEquals(2, $this->database->table($this->getClassProperty( 'table_name' ))->count()  );
        
    }
    
}