<?php
/**
 * This file is a part of the NDBF library
 *
 * @copyright (c) Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License can be found within the file license.txt in the root folder.
 *
 */

namespace NDBF;

/**
 * Basic repository class
 */
class Repository extends \Nette\Object
{
	/* ---------------------------- VARIABLES ------------------------------- */

	/** @var \Nette\Database\Connection */
	protected $connection;

	/** @var string Associated table name */
	protected $tableName;


	/* ------------------------ CONSTRUCTOR, DESIGN ------------------------- */

	public function __construct(\Nette\Database\Connection $connection, $tableName = null)
	{
		$this->connection = $connection;

		// DATABASE TABLE NAME
		if ($tableName === null) {
			$tableName = get_class($this);
			$tableName = substr($tableName, strrpos($tableName, '\\') + 1);
		}
		$this->tableName = strtolower($tableName); // Lowercase convention!
	}

	/* ---------------------- Nette\Database EXTENSION ---------------------- */

	/**
	 * @return \Nette\Database\Table\Selection
	 */
	final public function table()
	{
		return $this->connection->table($this->tableName);
	}

	/**
	 * @return \Nette\Database\Table\Selection
	 */
	final public function select($columns = '*')
	{
		return $this->connection->table($this->tableName)->select($columns);
	}

	/**
	 * Returns all rows as an associative array.
	 * @param  string
	 * @param  string column name used for an array value or an empty string for the whole row
	 * @return array
	 */
	public function fetchPairs($key, $val = '')
	{
		return $this->table()->fetchPairs($key, $val);
	}

	/**
	 * Counts table's rows.
	 * @param array,null $conditions
	 * @return int
	 */
	public function count()
	{
		return $this->table()->count();
	}

	/**
	 * Deletes entity from db.
	 * @param array $conditions
	 * @throws LogicException, InvalidArgumentException
	 */
	public function delete($conditions)
	{
		$this->connection->exec('DELETE FROM `' . $this->tableName . '` WHERE ', $conditions);
	}

	/**
	 * Saves record
	 * @param array $record
	 * @param string $tableId
	 */
	public function save(&$record, $tableId)
	{
		// If there is no ID, we MUST insert
		if (!isset($record[$tableId])) {
			$insert = true;
		} else {
			// There is an ID
			// Following condition allows restoring deleted items
			if ($this->select($tableId)->where($tableId, $record[$tableId])->fetch()) // Is this entity already stored?
				$insert = false; // Yes it is, so we'll update it
			else
				$insert = true; // No it isn't so insert
		}


		if ($insert) {
			$this->connection
					->exec('INSERT INTO `' . $this->tableName . '`', $record);

			// Set last inserted item id
			$record[$tableId] = $this->connection->lastInsertId();
		}else
			$this->connection
					->exec('UPDATE `' . $this->tableName . '` SET ? WHERE `' . $tableId . '` = ?', $record, $record[$tableId]);
	}

}
