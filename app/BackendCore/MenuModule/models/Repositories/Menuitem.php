<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace Application\Repository;

use \Nette\Caching\Cache;

class Menuitem extends \NDBF\Repository
{

    /** @var Nette\Caching\Cache */
    private $cache;

    /* FETCHING and usual database access */

    public function fetchStructured()
    {
        $mis = $this->find(null, '`order` ASC');
        $structuredMis = array();

        // I got to create new array, because when tried to modify the original one 
        // this popped up "Indirect modification of overloaded element of Nette\Database\Table\Selection has no effect"
        // Caching
        $modulesNames = $this->container->moduleManager->getModulesInfo();

        foreach ($mis as $mi) {

            if ($mi['type'] === \Application\Entity\Menuitem::TYPE_MODULE) {
                $mi['module_name_verbalname'] = $modulesNames[$mi['module_name']]['name'];
                $mi['module_view_verbalname'] = $modulesNames[$mi['module_name']]['methods'][$mi['module_view']];
            }

            if ($mi['parent'] === null) {
                // Is a top level item
                // If wasn't already created (in following section), copy into final menuitems
                if (!isset($structuredMis[$mi['id']])) {
                    $structuredMis[$mi['id']] = $mis[$mi['id']]->toArray();
                    $structuredMis[$mi['id']]['children'] = array();
                }
            } else {
                // Is child
                // If its parent wasn't initialized yet do:
                if (!isset($structuredMis[$mi['parent']]['children'])) {
                    $structuredMis[$mi['parent']] = $mis[$mi['parent']]->toArray();
                    $structuredMis[$mi['parent']]['children'] = array();
                }

                $structuredMis[$mi['parent']]['children'][$mi['id']] = $mi;

                unset($mis[$mi['id']]);
            }
        }

        // Submenus can be written in array earlier due to two lines following after "Possible parent, create array for children".
        uasort($structuredMis, function($itemA, $itemB) {
                    return ( $itemA['order'] < $itemB['order'] ? -1 : 1 );
                });

        return $structuredMis;
    }

    public function fetchSubmenusPairs()
    {
        return $this->find(array('type' => \Application\Entity\Menuitem::TYPE_SUBMENU))->fetchPairs('id', 'name');
    }

    public function save(&$mi, $table_id = 'id')
    {
        if (!isset($mi['order']))
            $mi['order'] = $this->getMaxOrder($mi['parent']) + 1;
        parent::save($mi, $table_id);
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
        $index = $this->getCache()->load('index');
        if ($index === null) {
            $index = $this->find(array('parent' => null, 'order' => 1))->fetch();
            $index = $index->toArray();
            $this->getCache()->save('index', $index, array(Cache::TAGS => array('ApplicationFrontMenu')));
        }

        return $index;
    }

    /**
     * Orders all menuitems
     */
    private function fixOrder()
    {
        $mis = $this->fetchStructured();
        $this->recursiveOrderFixer($mis);
    }

    /**
     * Fixes order numbers, e.g. from order [2,3,4] it makes [1,2,3]
     * @param array Given array has to be SORTED
     */
    private function recursiveOrderFixer($mis)
    {
        $i = 1;
        $orders = array();
        foreach ($mis as $id => $mi) {
            $orders[$id] = $i;

            if (isset($mi['children'])) {
                $this->recursiveOrderFixer($mi['children']);
            }

            $i++;
        }
        $this->orderUpdate($orders);
    }

    /**
     * Updates menuitems' order
     * @param array array( menuitem id => order)
     */
    public function orderUpdate($orders)
    {
        $this->db->beginTransaction();

        foreach ($orders as $id => $order) {
            $record = array('order' => $order);
            $this->db->exec('UPDATE ' . $this->table_name . ' SET ? WHERE id = ?', $record, $id);
        }

        $this->db->commit();
    }

    /**
     * Updates menuitems' parents
     * @param array array( menuitem id => its parent id)
     */
    public function parentsUpdate($parents)
    {
        $this->db->beginTransaction();

        foreach ($parents as $id => $parent) {
            $record = array('parent' => $parent);
            $this->db->exec('UPDATE ' . $this->table_name . ' SET ? WHERE id = ?', $record, $id);
        }

        $this->db->commit();
    }

    /**
     *
     * @param integer $parent Parent id
     * @return integer
     */
    public function getMaxOrder($parent)
    {
        if ($parent === NULL)
            return $this->table()->max('`order`');
        else
            return $this->table()->where('parent', $parent)->max('`order`');
    }

    /**
     * @return Nette\Caching\Cache
     */
    private function getCache()
    {
        if ($this->cache === null)
            $this->cache = new Cache($this->container->cacheStorage, 'Application.Front.Menu');

        return $this->cache;
    }

    public function cleanCache()
    {
        $this->getCache()->clean(array(Cache::TAGS => array('ApplicationFrontMenu')));
    }

}