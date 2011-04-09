<?php

/**
 * Set of unit tests for DatabaseMysql class
 *
 * $Id$
 */

class DatabaseMysqlTest extends PHPUnit_Framework_TestCase {

	private $lastQuery;

	// assert that given query matches the recent one
	private function assertQueryEquals($expected) {
		$this->assertEquals($expected, $this->lastQuery);
	}

	public function queryMock($query) {
		$this->lastQuery = $query;
		return false;
	}

	// @see http://www.php.net/manual/en/mysqli.real-escape-string.php
	public function escapeMock($value) {
		return addcslashes($value, "'\"\0");
	}

	public function testMySqlDatabaseMock() {
		// load MySQL driver
		$app = Nano::app(dirname(__FILE__) . '/app');
		$database = Database::connect($app, array('driver' => 'mysql'));

		// mock the database driver
		$database = $this->getMock('DatabaseMysql', array('query', 'escape', 'isConnected'), array(), 'DatabaseMysqlMock', false /* $callOriginalConstructor */);

		// mock certain methods
		$database->expects($this->any())
			->method('query')
			->will($this->returnCallback(array($this, 'queryMock')));

		$database->expects($this->any())
			->method('escape')
			->will($this->returnCallback(array($this, 'escapeMock')));

		$database->expects($this->any())
			->method('isConnected')
			->will($this->returnValue(true));

		// check mock
		$this->assertInstanceOf('DatabaseMysql', $database);
		$this->assertTrue($database->isConnected());

		// escape
		$this->assertEquals('foo\\"s', $database->escape('foo"s'));
		$this->assertEquals('foo\\\'s', $database->escape('foo\'s'));

		// test queries
		$database->query('SET foo = 1');
		$this->assertQueryEquals('SET foo = 1');

		$database->begin();
		$this->assertQueryEquals('BEGIN');
	}
}