<?php

/**
 * Database access layer
 *
 * $Id$
 */

abstract class Database {

	/**
	 * Connect to a given database
	 */
	public static function connect(NanoApp $app, Array $settings) {
		// TODO
		$instance = new Database();

		return $instance;
	}

	/**
	 * Send given query and return results handler
	 */
	abstract public function query($sql);

	/**
	 * Properly encode given string
	 */
	abstract public function escape($value);

	/**
	 * Select given fields from a table using following WHERE statements
	 */
	abstract public function select($table, Array $fields, $where, Array $options = array());

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	abstract public function selectRow($table, Array $fields, $where, Array $options = array());

	/**
	 * Select given fields from a table using following WHERE statements (return single field)
	 */
	abstract public function selectField($table, $field, $where, Array $options = array());

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
	 * Get information about current connection
	 */
	abstract public function getInfo();
}