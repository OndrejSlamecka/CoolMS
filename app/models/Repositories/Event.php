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


class Event extends \NDBF\Repository
{
  
    public function find($conditions = null, $order = null, $offset = null, $limit = null)
    {
        // Start basic command
        $query = $this->db->table($this->table_name)->select('*,YEAR(date) as year');
        
        // Apply conditions
        $query = $this->applyConditions( $query, $conditions );

        // Apply order
        if( isset($order) )
            $query = $query->order( $order );

        if( isset($limit) ){
            if( $offset !== null )
                $query = $query->limit($limit,$offset);
            else
                $query = $query->limit($limit);
        }

        return $query;
    }

    
    public function fetchAssocByYear()
    {
        $events = $this->table()->select('*,YEAR(date) as year')->order('year ASC');
        
        $years = array();
        foreach( $events as $event ){
            if( !isset($years[$event['year']]) ) $years[$event['year']] = array();
            $years[$event['year']][] = $event;
        }
        return $years;        
    }
    
    public function getYears()
    {
        return $this->table()->select('YEAR(date) as year')->order('year ASC')->group('year')->fetchPairs('year');
    }

}