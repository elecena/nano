<?php

namespace Nano;
use NanoApp;

/**
 * Base class for PHPUnit-based unit tests of database-related code
 */
class NanoDatabaseMock extends \DatabaseMysql {

	private $onQueryCallback, $result;

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
	 * @param \DatabaseResult|mixed $result
	 */
	public function setResult($result) {
		$this->result = $result;
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
	 * @return mixed
	 */
	public function query($sql, $fname = false) {
		if (is_callable($this->onQueryCallback)) {
			call_user_func($this->onQueryCallback, func_get_args());
		}

		$this->lastQuery = $sql;

		return $this->result;
	}
}
