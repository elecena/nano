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
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		$this->link = mysqli_init();

		// set UTF8 as connection encoding
		if (!empty($settings['utf'])) {
			$this->link->options(MYSQLI_INIT_COMMAND, 'SET NAMES "utf8"');
		}

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
				$this->debug->log(__METHOD__ . ' - connected with ' . $settings['host']);

				$this->connected = true;
			}
			else {
				$errorMsg = trim(mysqli_connect_error());

				$this->debug->log(__METHOD__ . ' - connecting with ' . $settings['host'] . ' failed (' . $errorMsg .')', Debug::ERROR);

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
	 *
	 * @see http://www.php.net/manual/en/mysqli.real-query.php
	 */
	public function query($sql) {
		$time = microtime(true);
		$res = $this->link->query($sql, MYSQLI_USE_RESULT);
		$time = microtime(true) - $time;

		// update stats
		$this->queries++;
		$this->queriesTime += $time;

		// log query
		$timeFormatted = ' [' . sprintf('%.3f', $time) . ' s]';

		$this->debug->log(__METHOD__ . ': ' . $sql . $timeFormatted, Debug::NOTICE);

		// check for errors
		if (empty($res)) {
			$this->debug->log(__METHOD__ . ': ' . $this->link->error, Debug::ERROR);

			// TODO: raise an excpetion

			return false;
		}

		// wrap results into iterator
		return new DatabaseResult($this, $res);
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
		return !empty($this->link) ? $this->link->insert_id : 0;
	}

	/**
	 * Get number of rows affected by the recent query
	 */
	public function getRowsAffected() {

	}

	/**
	 * Get number of rows in given results set
	 */
	public function numRows($results) {
		return $results->num_rows;
	}

	/**
	 * Change the position of results cursor
	 */
	public function seekRow($results, $rowId) {
		$results->data_seek($rowId);
	}

	/**
	 * Get data for current row
	 */
	public function fetchRow($results) {
		$row = $results->fetch_assoc();

		return !is_null($row) ? $row : false;
	}

	/**
	 * Free the memory
	 */
	public function freeResults($results) {
		$results->free_result();
	}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return $this->isConnected() ? $this->link->server_info : '';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return $this->connected == true;
	}
}