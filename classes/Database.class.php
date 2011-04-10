<?php

/**
 * Database access layer
 *
 * $Id$
 */

abstract class Database {

	// debug
	protected $debug;

	// connection resource
	protected $link;

	// indicates that connection was successfully established
	protected $connected = false;

	/**
	 * Force constructors to be protected - use Database::connect
	 */
	abstract protected function __construct(Array $settings);

	/**
	 * Connect to a given database
	 */
	public static function connect(NanoApp $app, Array $settings) {
		$driver = isset($settings['driver']) ? $settings['driver'] : null;
		$instance = null;

		if (!empty($driver)) {
			$className = 'Database' . ucfirst(strtolower($driver));

			$src = dirname(__FILE__) . '/database/' . $className . '.class.php';

			if (file_exists($src)) {
				require_once $src;

				try {
					$instance = new $className($settings);

					// use debugger from the application
					$instance->debug = $app->getDebug();
				}
				catch(Exception $e) {
					// TODO: handle exception
					//var_dump($e->getMessage());
				}
			}
		}

		return $instance;
	}

	/**
	 * Close the current connection
	 */
	abstract public function close();

	/**
	 * Ping the current connection
	 */
	abstract public function ping();

	/**
	 * Send given query and return results handler
	 */
	abstract public function query($sql);

	/**
	 * Start a transaction
	 */
	abstract public function begin();

	/**
	 * Commit the current transaction
	 */
	abstract public function commit();

	/**
	 * Rollback the current transaction
	 */
	abstract public function rollback();

	/**
	 * Properly encode given string
	 */
	abstract public function escape($value);

	/**
	 * Select given fields from a table using following WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/select.html
	 */
	public function select($table, $fields, $where = array(), Array $options = array()) {
		$sql = 'SELECT ' . $this->resolveList($fields) . ' FROM ' . $this->resolveList($table);

		$whereSql = $this->resolveWhere($where);
		if (!empty($whereSql)) {
			$sql .= ' WHERE ' . $whereSql;
		}

		$optionsSql = $this->resolveOptions($options);
		if (!empty($optionsSql)) {
			$sql .= ' ' . $optionsSql;
		}

		return $this->query($sql);
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	public function selectRow($table, $fields, $where = array(), Array $options = array()) {
		$options['limit'] = 1;
		$res = $this->select($table, $fields, $where, $options);

		if (!empty($res)) {
			$ret = $res->fetchRow();

			$res->free();
			return $ret;
		}
		else {
			return false;
		}
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single field)
	 */
	public function selectField($table, $field, $where = array(), Array $options = array()) {
		$row = $this->selectRow($table, $field, $where, $options);

		return !empty($row) ? $row[0] : false;
	}

	/**
	 * Remove rows from a table using following WHERE statements
	 */
	abstract public function delete($table, $where, Array $options = array());

	/**
	 * Update a table using following values for rows matching WHERE statements
	 */
	abstract public function update($table, Array $value, $where, Array $options = array());

	/**
	 * Insert a single row into a table using following values
	 */
	abstract public function insert($table, Array $row, Array $options = array());

	/**
	 * Insert multiple rows into a table using following values
	 */
	abstract public function insertRows($table, Array $rows, Array $options = array());

	/**
	 * Get primary key value for recently inserted row
	 */
	abstract public function getInsertId();

	/**
	 * Get number of rows affected by the recent query
	 */
	abstract public function getRowsAffected();

	/**
	 * Get number of rows in given results set
	 */
	abstract public function numRows($results);

	/**
	 * Change the position of results cursor
	 */
	abstract public function seekRow($results, $rowId);

	/**
	 * Get data for current row
	 */
	abstract public function fetchRow($results);

	/**
	 * Free the memory
	 */
	abstract public function freeResults($results);

	/**
	 * Get information about current connection
	 */
	abstract public function getInfo();

	/**
	 * Return true if currently connected to the database
	 */
	abstract public function isConnected();

	/**
	 * Return part of SQL for given list of values
	 */
	public function resolveList($values) {
		if (is_array($values)) {
			$sql = implode(',', $values);
		}
		else {
			$sql = $values;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given WHERE statements
	 */
	public function resolveWhere($where) {
		if (is_string($where)) {
			$sql = $where;
		}
		else if (is_array($where)) {
			$sqlParts = array();

			foreach($where as $field => $cond) {
				if (is_numeric($field)) {
					$sqlParts[] = $cond;
				}
				else {
					$sqlParts[] = $field . '="' . $this->escape($cond) . '"';
				}
			}

			$sql = implode(' AND ', $sqlParts);
		}
		else {
			$sql = false;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given ORDER BY conditions
	 */
	public function resolveOrderBy($orderBy) {
		if (is_string($orderBy)) {
			$sql = $orderBy;
		}
		else if (is_array($orderBy)) {
			$sqlParts = array();

			foreach($orderBy as $field => $cond) {
				if (is_numeric($field)) {
					$sqlParts[] = $cond;
				}
				else {
					$sqlParts[] = $field . ' ' . $cond;
				}
			}

			$sql = implode(',', $sqlParts);
		}
		else {
			$sql = false;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given set of options
	 */
	public function resolveOptions(Array $options) {
		$sqlParts = array();

		if (isset($options['order'])) {
			$sqlParts[] = 'ORDER BY ' . $this->resolveOrderBy($options['order']);
		}

		if (isset($options['limit'])) {
			$sqlParts[] = 'LIMIT ' . intval($options['limit']);
		}

		if (isset($options['offset'])) {
			$sqlParts[] = 'OFFSET ' . intval($options['offset']);
		}

		// parse options it they were provided as a simple list
		if (empty($sqlParts)) {
			foreach($options as $opt) {
				$sqlParts[] = $opt;
			}
		}

		return implode(' ', $sqlParts);
	}
}