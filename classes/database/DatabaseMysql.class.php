<?php

/**
 * Database access layer for mySQL
 *
 * $Id$
 */

class DatabaseMysql extends Database {

	/**
	 * Connect to a database
	 */
	protected function __construct(Array $settings) {
		$this->link = mysqli_init();

		// set UTF8 as connection encoding
		$this->link->options(MYSQLI_INIT_COMMAND, 'SET NAMES "utf8"');

		// prepare connection settings
		// @see http://www.php.net/manual/en/mysqli.real-connect.php
		$params = array();
		$keys = array('host', 'user', 'pass', 'database', 'port', 'socket');

		foreach($keys as $key) {
			if (isset($settings[$key])) {
				$params[] = $settings[$key];
			}
			else {
				break;
			}
		}

		// try to connect
		if (!empty($params)) {
			if (@call_user_func_array(array($this->link, 'real_connect'), $params)) {
				$this->connected = true;
			}
			else {
				$errorMsg = trim(mysqli_connect_error());
				throw new Exception($errorMsg);
			}
		}
	}

	/**
	 * Close the current connection
	 */
	public function close() {
		if ($this->isConnected()) {
			$this->link->close();
			$this->connected = false;

			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Ping the current connection
	 */
	public function ping() {
		return $this->isConnected() && $this->link->ping();
	}

	/**
	 * Send given query and return results handler
	 */
	public function query($sql, $resultmode =  MYSQLI_STORE_RESULT) {
		return $this->link->query($sql, $resultmode);
	}

	/**
	 * Start a transaction
	 */
	public function begin() {
		$this->query('BEGIN');
	}

	/**
	 * Commit the current transaction
	 */
	public function commit() {
		return $this->link->commit();
	}

	/**
	 * Rollback the current transaction
	 */
	public function rollback() {
		return $this->link->rollback();
	}

	/**
	 * Properly encode given string
	 */
	public function escape($value) {
		//return $this->link->escape_string($value);
		return mysql_escape_string($value);
	}

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

		$res = $this->query($sql);

		return $res;
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	public function selectRow($table, $fields, $where = array(), Array $options = array()) {

	}

	/**
	 * Select given fields from a table using following WHERE statements (return single field)
	 */
	public function selectField($table, $field, $where = array(), Array $options = array()) {

	}

	/**
	 * Remove rows from a table using following WHERE statements
	 */
	public function delete($table, $where, Array $options = array()) {

	}

	/**
	 * Update a table using following values for rows matching WHERE statements
	 */
	public function update($table, Array $value, $where, Array $options = array()) {

	}

	/**
	 * Insert a single row into a table using following values
	 */
	public function insert($table, Array $row, Array $options = array()) {

	}

	/**
	 * Insert multiple rows into a table using following values
	 */
	public function insertRows($table, Array $rows, Array $options = array()) {

	}

	/**
	 * Get primary key value for recently inserted row
	 */
	public function getInsertId() {

	}

	/**
	 * Get number of rows affected by the recent query
	 */
	public function getRowsAffected() {

	}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return $this->isConnected() ? $this->link->host_info : '';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return $this->connected == true;
	}
}