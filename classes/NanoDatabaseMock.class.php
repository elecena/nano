<?php

namespace Nano;
use NanoApp;

/**
 * Base class for PHPUnit-based unit tests of database-related code
 */
class NanoDatabaseMock extends \DatabaseMysql {

	private $onQueryCallback;
	/* @var array $result */
	private $result;
	private $currentRow = 0;

	/**
	 * @param NanoApp $app
	 */
	public function __construct($app) {
		parent::__construct($app, [], 'test');
	}

	/**
	 * @param callable|null $callback
	 */
	public function setOnQueryCallback(callable $callback = null) {
		$this->onQueryCallback = $callback;
	}

	/**
	 * @param array $result
	 */
	public function setResult(array $result) {
		$this->result = $result;
	}

	/**
	 * @param array $row
	 */
	public function setResultRow(array $row) {
		$this->setResult([$row]);
	}

	/**
	 * Properly encode given string
	 *
	 * @see http://www.php.net/manual/en/mysqli.real-escape-string.php
	 *
	 * @param string $value
	 * @return string
	 */
	public function escape($value) {
		return addcslashes($value, "'\"\0");
	}

	/**
	 * Return mocked result and call a callback with a provided SQL query
	 *
	 * @param string $sql
	 * @param string|bool $fname
	 * @return \DatabaseResult
	 */
	public function query($sql, $fname = false) {
		if (is_callable($this->onQueryCallback)) {
			call_user_func($this->onQueryCallback, func_get_args());
		}

		# var_dump(__METHOD__, $sql);

		$this->lastQuery = $sql;

		return new \DatabaseResult($this, $this->result);
	}

	/**
	 * @param \mysqli_result $results
	 * @return int
	 */
	public function numRows($results) {
		return count($this->result);
	}

	/**
	 * Change the position of results cursor
	 * @param \mysqli_result $results
	 * @param int $rowId
	 */
	public function seekRow($results, $rowId) {
		$this->currentRow = $rowId;
	}

	/**
	 * Get data for current row
	 *
	 * @param \mysqli_result $results
	 * @return mixed|false
	 */
	public function fetchRow($results) {
		$row = !empty($this->result[$this->currentRow]) ? $this->result[$this->currentRow] : null;

		// move to the next row
		$this->currentRow++;

		return !is_null($row) ? $row : false;
	}

	/**
	 * Free the memory
	 *
	 * @param \mysqli_result $results
	 */
	public function freeResults($results) {
		// noop
	}

}
