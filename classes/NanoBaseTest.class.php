<?php

namespace Nano;

use PHPUnit\Framework\TestCase;

/**
 * Base class for PHPUnit-based unit tests
 */
class NanoBaseTest extends TestCase {

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
	 * @param array $result
	 * @param callable|null $onQuery optional callback
	 * @return NanoDatabaseMock
	 */
	protected function getDatabaseMock(array $result = [], callable $onQuery = null) {
		$mock = new NanoDatabaseMock($this->app);

		$mock->setOnQueryCallback($onQuery);
		$mock->setResult($result);

		return $mock;
	}

	/**
	 * Creates a mock of NanoApp with a given method mocked
	 * @param string $method
	 * @param mixed $value
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getNanoAppMock($method, $value) {
		$mock = $this->createMock(\NanoApp::class);
		$mock->method($method)->willReturn($value);

		return $mock;
	}

	/**
	 * @param NanoObject $obj
	 * @param \PHPUnit_Framework_MockObject_MockObject $mock
	 */
	protected function setNanoAppMock(NanoObject $obj, \PHPUnit_Framework_MockObject_MockObject $mock) {
		// make sphinx property a public one
		$reflection = new \ReflectionClass($obj);
		$reflection_property = $reflection->getProperty('app');
		$reflection_property->setAccessible(true);

		$reflection_property->setValue($obj, $mock);
	}
}
