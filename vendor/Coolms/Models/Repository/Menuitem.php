<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Coolms\Repository;

use \Nette\Caching\Cache;

class Menuitem extends \NDBF\Repository
{

	/** @var \Coolms\Modules */
	private $modules;

	/** @var \Nette\Caching\IStorage */
	private $cacheStorage;

	/** @var \Nette\Caching\Cache */
	private $cache;

	public function setContainer(\Coolms\Modules $modules, \Nette\Caching\IStorage $cacheStorage)
	{
		$this->modules = $modules;
		$this->cacheStorage = $cacheStorage;
	}

	/* FETCHING and usual database access */

	private function fetchBranch($items, $modulesNames)
	{
		$branch = array();
		foreach ($items as $key => $item) {
			$aItem = $item->toArray();

			if ($aItem['type'] === \Coolms\Entity\Menuitem::TYPE_MODULE) {
				$aItem['module_name_verbalname'] = $modulesNames[$item['module_name']]['name'];
				$aItem['module_view_verbalname'] = $modulesNames[$item['module_name']]['views'][$item['module_view']];
			} else {
				$children = $item->related('menuitem')->order('`order`');
				$aItem['children'] = $this->{__FUNCTION__}($children, $modulesNames);
			}

			$branch[$key] = $aItem;
		}

		return $branch;
	}

	public function fetchStructured()
	{
		$modulesNames = $this->modules->getModules();
		$items = $this->select()->where('menuitem_id', NULL)->order('`order`');
		$tree = $this->fetchBranch($items, $modulesNames);
		return $tree;
	}

	public function fetchSubmenusPairs()
	{
		return $this->select()->where('type', \Coolms\Entity\Menuitem::TYPE_SUBMENU)->fetchPairs('id', 'name');
	}

	public function save(&$mi, $table_id = 'id')
	{
		if (!isset($mi['order']))
			$mi['order'] = $this->getMaxOrder($mi['menuitem_id']) + 1;
		parent::save($mi, $table_id);
		$this->cleanCache();
	}

	public function remove($conditions)
	{
		parent::delete($conditions);
		$this->fixOrder();
	}

	/* STRUCTURE */

	public function getIndex()
	{
		$index = $this->getCache()->load('index');
		if ($index === null) {
			$index = $this->select()->where(array('menuitem_id' => null, 'order' => 1))->fetch();
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
		$this->connection->beginTransaction();

		foreach ($orders as $id => $order) {
			$record = array('order' => $order);
			$this->connection->exec('UPDATE ' . $this->tableName . ' SET ? WHERE id = ?', $record, $id);
		}

		$this->connection->commit();
	}

	/**
	 * Updates menuitems' parents
	 * @param array array( menuitem id => its parent id)
	 */
	public function parentsUpdate($parents)
	{
		$this->connection->beginTransaction();

		foreach ($parents as $id => $parent) {
			$record = array('menuitem_id' => $parent);
			$this->connection->exec('UPDATE ' . $this->tableName . ' SET ? WHERE id = ?', $record, $id);
		}

		$this->connection->commit();
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
			return $this->table()->where('menuitem_id', $parent)->max('`order`');
	}

	/**
	 * @return Nette\Caching\Cache
	 */
	private function getCache()
	{
		if ($this->cache === null)
			$this->cache = new Cache($this->cacheStorage, 'Application.Front.Menu');

		return $this->cache;
	}

	public function cleanCache()
	{
		$this->getCache()->clean(array(Cache::TAGS => array('ApplicationFrontMenu')));
	}

}