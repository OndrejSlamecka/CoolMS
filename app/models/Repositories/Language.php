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

class Language extends \NDBF\Repository
{
    
    public function fetchPairs($key="code",$val="name")
    {
        return parent::fetchPairs($key,$val);
    }

}