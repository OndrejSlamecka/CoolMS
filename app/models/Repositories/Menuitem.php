<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace App\Repository;

use \Nette\Caching\Cache;

class Menuitem extends \NDBF\Repository
{

    /** @var Nette\Caching\Cache */	    
    private $cache;    
    
    /* FETCHING and usual database access */
    
    public function fetchStructured()
    {
        $mis = $this->find( null, "order" );
        $structuredMis = array();               

        // I got to create new array, because when tried to modify the original one 
        // this popped up "Indirect modification of overloaded element of Nette\Database\Table\Selection has no effect"

        // Caching
        $modulesNames = $this->container->moduleManager->getModulesInfo();
        
        foreach( $mis as $mi ){

            if( $mi['type'] === \App\Entity\Menuitem::TYPE_MODULE ){                               
                $mi['module_name_verbalname'] = $modulesNames[ $mi['module_name'] ]['name'];                
                $mi['module_view_verbalname'] = $modulesNames[ $mi['module_name'] ]['methods'][$mi['module_view']];
            }
            
            if( $mi['parent'] === null ){
            // Is top level item
                
                // Copy into final menuitems
                $structuredMis[ $mi['id'] ] = $mis[ $mi['id'] ]->toArray();

                // Possible parent, create array for children
                if( !isset( $structuredMis[ $mi['id'] ]['children'] ) )
                    $structuredMis[ $mi['id'] ]['children'] = array();  
                
            }else{
            // Is child
                // If its parent wasn't initialized yet. This could happen only when child was created BEFORE parent (only possible if it was moved)
                if( !isset( $structuredMis[ $mi['parent'] ]['children'] ) ){
                    $structuredMis[$mi['parent']] = $mis[ $mi['parent'] ]->toArray();
                    $structuredMis[ $mi['parent'] ]['children'] = array();
                }                                
                $structuredMis[ $mi['parent'] ]['children'][$mi['id']] = $mi;
                unset($mis[ $mi['id'] ]);
            }
        }

        return $structuredMis;
    }
      
    public function fetchSubmenusPairs()
    {        
        return $this->find( array( 'type'=>\App\Entity\Menuitem::TYPE_SUBMENU ) )->fetchPairs( 'id', 'name' );
    }
    
    public function save( &$mi, $table_id = 'id' )
    {
        if( !isset($mi['order']) )
            $mi['order'] = $this->getMaxOrder( $mi['parent'] ) +1;
	parent::save( $mi, $table_id );
        $this->cleanCache();
    }    
    
    public function remove($conditions)
    {
        parent::remove($conditions);  
        $this->fixOrder();  
    }
    
    
    /* STRUCTURE */
    
    public function getIndex()
    {        
        $index = $this->getCache()->load( 'index' );        
        if( $index === null ){
            $index = $this->find(array( 'parent' => null, 'order' => 1 ))->fetch();
            $index = $index->toArray();
            $this->getCache()->save( 'index', $index, array( Cache::TAGS => array( 'AppFrontMenu' ) ) );
        }
        
        return $index;        
    }
    
    /**
     * Orders all menuitems
     */
    public function fixOrder()
    {               
        $mis = $this->fetchStructured();        
        $this->recursiveOrderFixer( $mis );
    }
    
    // TODO: Needs rewriting after rewriting orderUpdate... hell
    private function recursiveOrderFixer($mis)
    {
        $i=1;
        foreach( $mis as $mi ){
            $this->orderUpdate($mi['id'], $i);
                    
            if( isset( $mi['children'] ) ){
                $this->recursiveOrderFixer( $mi['children'] );
            }
            
            $i++;
        }
    }
    
    
    // TODO: Give all pairs and use the (f-word) transaction!!
    public function orderUpdate( $id, $order )
    {        
        $order = array( 'order' => $order, 'id' => $id );
	$this->save( $order, 'id' );
    }
    
    // TODO: Give all pairs and use the (f-word) transaction!!
    public function parentsUpdate( $id, $parents )
    {
        $parents = array( 'parent' => $parents, 'id' => $id );
	$this->save( $parents, 'id' );
    }   
    
    /**
     *
     * @param integer $parent Parent id
     * @return integer
     */
    public function getMaxOrder( $parent )
    {
	if( $parent === NULL )
	    return $this->table()->max('order');
	else
	    return $this->table()->where('parent',$parent)->max('order');
    }        
    
    /**
     * @return Nette\Caching\Cache
     */
    private function getCache()
    {
        if ($this->cache === null)
            $this->cache = new Cache($this->container->cacheStorage, 'App.Front.Menu');        
        
        return $this->cache;
    }    
    
    public function cleanCache()
    {
        $this->getCache()->clean( array( Cache::TAGS => array( 'AppFrontMenu' ) ) );
    }

}