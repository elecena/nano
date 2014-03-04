<?php

/**
 * Wrapper for database query results
 *
 * @see http://pl2.php.net/manual/en/class.iterator.php
 */

class DatabaseResult implements Iterator, Countable {

	// database driver
	protected $database;

	// results resource
	protected $results;

	// current position in results
	protected $pos;

	// current row
	protected $currentRow;

	function __construct(Database $database, $results) {
		$this->database = $database;
		$this->results = $results;

		// reset position
		$this->pos = 0;
	}

	/**
	 * Return number of rows in result
	 */
	public function numRows() {
		return $this->database->numRows($this->results);
	}

	/**
	 * Change the position of results cursor
	 */
	public function seek($rowId) {
		$this->database->seekRow($this->results, $rowId);
	}

	/**
	 * Return data from current row
	 *
	 * @return mixed data
	 */
	public function fetchRow() {
		return $this->database->fetchRow($this->results);
	}

	/**
	 * Return first field from current row
	 */
	public function fetchField() {
		$row = $this->fetchRow();

		return !empty($row) ? reset($row) : false;
	}

	/**
	 * Free results
	 */
	public function free() {
		$this->database->freeResults($this->results);

		unset($this->database);
		unset($this->results);
		unset($this->currentRow);
	}

	/**
	 * Implement Countable interface
	 */
	public function count() {
		return $this->numRows();
	}

	/**
	 * Implement Iterator interface
	 */
	public function rewind() {
		if ($this->numRows()) {
			$this->seek(0);
		}
		$this->pos = 0;
		$this->currentRow = null;
	}

	public function current() {
		if (is_null($this->currentRow)) {
			$this->next();
		}

		return $this->currentRow;
	}

	public function key() {
		return $this->pos;
	}

	public function next() {
		$this->pos++;
		$this->currentRow = $this->fetchRow();
		return $this->currentRow;
	}

	public function valid() {
		return $this->current() !== false;
	}
}