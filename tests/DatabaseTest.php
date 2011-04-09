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

	public function testMySqlDatabaseFactory() {
		$app = $this->buildApp();

		// don't actually connect
		$database = Database::connect($app, array('driver' => 'mysql'));

		$this->assertInstanceOf('DatabaseMysql', $database);

		$this->assertFalse($database->isConnected());
	}

	// this test requires a running instance of mySQL on localhost:3306
	public function testMySqlDatabaseConnect() {
		$app = $this->buildApp();

		$database = Database::connect($app, array('driver' => 'mysql', 'host' => 'localhost', 'user' => 'root'));

		$this->assertInstanceOf('DatabaseMysql', $database);
		$this->assertTrue($database->isConnected());
		$this->assertContains('localhost', $database->getInfo());

		// string escaping
		$this->assertEquals('\\"foo\\"', $database->escape('"foo"'));
		$this->assertEquals('\\\'foo\\\'', $database->escape('\'foo\''));
		$this->assertEquals('%_test_%', $database->escape('%_test_%'));
	}
}