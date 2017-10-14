<?php

namespace Nano;

/**
 * Base class for PHPUnit-based unit tests
 */
class NanoBaseTest extends \PHPUnit_Framework_TestCase {

	/* @var $app \NanoApp */
	protected $app;

	protected function setUp() {
		// use the current working directory where "./composer.phar test" is run
		$dir = getcwd();
		$this->app = \Nano::app($dir);
	}

	/**
	 * Creates a Database class instance that will mock results of a given query
	 *
	 * @param $result
	 * @param callable|null $onQuery optional callback
	 * @return NanoDatabaseMock
	 */
	protected function getDatabaseMock($result, callable $onQuery = null) {
		$mock = new NanoDatabaseMock($this->app);

		$mock->setOnQueryCallback($onQuery);
		$mock->setResult($result);

		return $mock;
	}
}
