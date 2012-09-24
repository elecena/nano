<?php

/**
 * Database access layer for MongoDB
 *
 * $Id$
 */

class DatabaseMongo extends Database {

	private $db;

	/**
	 * Connect to a database
	 */
	protected function __construct(NanoApp $app, Array $settings, $name) {
		parent::__construct($app, $settings, $name);

		// mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
		$dsn = sprintf('mongodb://%s:%d/%s',
			$settings['host'],
			isset($settings['port']) ? $settings['port']: Mongo::DEFAULT_PORT,
			$settings['database']
		);
		$this->log(__METHOD__, 'connecting with "' . $dsn . '"...');

		// // @see http://php.net/manual/en/mongo.construct.php
		$this->link = new Mongo($dsn, array(
			'username' => $settings['user'],
			'password' => $settings['pass'],
		));

		$this->db = $this->link->selectDB($settings['database']);

		$this->log(__METHOD__, 'connected');
	}
	
	/**
	 * Start benchmarking current query
	 */
	protected function time() {
		$this->debug->time('query');
	}
	
	/**
	 * Finish benchmarking current query and update the stats
	 */
	protected function timeEnd() {
		$time = $this->debug->timeEnd('query');
		$this->queries++;
		$this->queriesTime += $time;
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
	public function ping() {}

	/**
	 * Send given query and return results handler
	 *
	 * @see http://www.php.net/manual/en/mysqli.real-query.php
	 */
	public function query($sql) {}

	/**
	 * Start a transaction
	 */
	public function begin($fname = 'Database::begin') {}

	/**
	 * Commit the current transaction
	 */
	public function commit($fname = 'Database::commit') {}

	/**
	 * Rollback the current transaction
	 */
	public function rollback() {}

	/**
	 * Properly encode given string
	 */
	public function escape($value) {}

	/**
	 * Remove rows from a table using following WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/delete.html
	 */
	public function delete($table, $where = array(), Array $options = array(), $fname = 'Database::delete') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: REMOVE WHERE " . json_encode($where));

		$this->time();
		$this->db->selectCollection($table)->remove($where, $options);
		$this->timeEnd();
	}

	/**
	 * Remove single row from a table using following WHERE statements
	 */
	public function deleteRow($table, $where = array(), $fname = 'Database::deleteRow') {
		return $this->delete($table, $where, array(
			'justOne' => true // LIMIT 1
		), $fname);
	}

	/**
	 * Update a table using following values for rows matching WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.5/en/update.html
	 */
	public function update($table, Array $values, $where = array(), Array $options = array(), $fname = 'Database::update') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: UPDATE " . json_encode($values) . ' WHERE ' . json_encode($where));

		

		// LIMIT 1
		$options['multiple'] = !empty($options['multiple']);

		$this->time();
		$this->db->selectCollection($table)->update($where, $values, $options);
		$this->timeEnd();
	}

	/**
	 * Insert a single row into a table using following values
	 */
	public function insert($table, Array $row, Array $options = array(), $fname = 'Database::insert') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: INSERT " . json_encode($row));

		$this->time();
		$this->db->selectCollection($table)->insert($row);
		$this->timeEnd();

		return $row['_id'];
	}

	/**
	 * Insert multiple rows into a table using following values
	 */
	public function insertRows($table, Array $rows, Array $options = array(), $fname = 'Database::insertRows') {
	}

	/**
	 * Get primary key value for recently inserted row
	 */
	public function getInsertId() {}

	/**
	 * Get number of rows affected by the recent query
	 */
	public function getRowsAffected() {}

	/**
	 * Get number of rows in given results set
	 */
	public function numRows($results) {}

	/**
	 * Change the position of results cursor
	 */
	public function seekRow($results, $rowId) {}

	/**
	 * Get data for current row
	 */
	public function fetchRow($results) {}

	/**
	 * Free the memory
	 */
	public function freeResults($results) {}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return $this->isConnected() ? 'MongoDB' : '';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return $this->link->connected;
	}
}
