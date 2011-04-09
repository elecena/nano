<?php

/**
 * Set of unit tests for Database class
 *
 * $Id$
 */

class DatabaseTest extends PHPUnit_Framework_TestCase {

	private function buildApp() {
		$app = Nano::app(dirname(__FILE__) . '/app');

		$app->getDebug()->enableLog();
		$app->getDebug()->setLogFile('database');

		return $app;
	}

	private function getDatabaseMySql() {
		$app = $this->buildApp();
		return Database::connect($app, array('driver' => 'mysql'));
	}

	public function testDatabaseConnectFactory() {
		$app = $this->buildApp();

		$database = Database::connect($app, array());
		$this->assertNull($database);

		$database = Database::connect($app, array('driver' => 'unknown'));
		$this->assertNull($database);

		// existing driver (don't actually connect)
		$database = Database::connect($app, array('driver' => 'mysql'));
		$this->assertInstanceOf('DatabaseMysql', $database);
	}

	public function testDatabaseMySql() {
		$database = $this->getDatabaseMySql();

		$this->assertInstanceOf('DatabaseMysql', $database);
		$this->assertFalse($database->isConnected());
	}

	public function testResolveList() {
		$database = $this->getDatabaseMySql();

		// test fields SQL
		$listSql = $database->resolveList('*');
		$this->assertEquals('*', $listSql);

		$listSql = $database->resolveList('pages');
		$this->assertEquals('pages', $listSql);

		$listSql = $database->resolveList(array('pages', 'users'));
		$this->assertEquals('pages,users', $listSql);
	}

	public function testResolveWhere() {
		$database = $this->getDatabaseMySql();

		// test where statements
		$whereSql = $database->resolveWhere(false);
		$this->assertFalse($whereSql);

		$whereSql = $database->resolveWhere('foo = bar');
		$this->assertEquals('foo = bar', $whereSql);

		$whereSql = $database->resolveWhere('foo = bar OR test > 123');
		$this->assertEquals('foo = bar OR test > 123', $whereSql);

		$whereSql = $database->resolveWhere(array('foo = bar'));
		$this->assertEquals('foo = bar', $whereSql);

		$whereSql = $database->resolveWhere(array('foo = bar', 'test > 123'));
		$this->assertEquals('foo = bar AND test > 123', $whereSql);

		$whereSql = $database->resolveWhere(array('foo' => 'bar', 'test > 123'));
		$this->assertEquals('foo="bar" AND test > 123', $whereSql);

		$whereSql = $database->resolveWhere(array('foo' => 'bar', 'test' => '123'));
		$this->assertEquals('foo="bar" AND test="123"', $whereSql);
	}

	public function testResolveOptions() {
		$database = $this->getDatabaseMySql();

		// test options
		$optionsSql = $database->resolveOptions(array());
		$this->assertEquals('', $optionsSql);

		$optionsSql = $database->resolveOptions(array('limit' => '15'));
		$this->assertEquals('LIMIT 15', $optionsSql);

		$optionsSql = $database->resolveOptions(array('order' => 'id'));
		$this->assertEquals('ORDER BY id', $optionsSql);

		$optionsSql = $database->resolveOptions(array('offset' => 2, 'limit' => '5'));
		$this->assertEquals('LIMIT 5 OFFSET 2', $optionsSql);

		$optionsSql = $database->resolveOptions(array('offset' => 2, 'limit' => '5', 'order' => 'foo DESC'));
		$this->assertEquals('ORDER BY foo DESC LIMIT 5 OFFSET 2', $optionsSql);
	}
}