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
		$this->assertFalse($database->isConnected());
	}
}