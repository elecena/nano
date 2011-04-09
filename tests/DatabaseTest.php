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

	public function testDatabase() {
		$app = $this->buildApp();

		// existing driver (don't actually connect)
		$database = Database::connect($app, array('driver' => 'mysql'));

		$this->assertInstanceOf('DatabaseMysql', $database);
		$this->assertFalse($database->isConnected());
		
		// test fields SQL
		$listSql = $database->resolveList('*');
		$this->assertEquals('*', $listSql);
		
		$listSql = $database->resolveList('pages');
		$this->assertEquals('pages', $listSql);
		
		$listSql = $database->resolveList(array('pages', 'users'));
		$this->assertEquals('pages,users', $listSql);

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
}