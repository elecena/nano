<?php

/**
 * Set of unit tests for Debug class
 *
 * $Id$
 */

class DebugTest extends PHPUnit_Framework_TestCase {

	private $logDir;

	public function setUp() {
		$this->logDir = dirname(__FILE__) . '/app/log';
	}

	public function testDirectory() {
		$debug = new Debug($this->logDir);
		$this->assertEquals($this->logDir . '/debug.log', $debug->getLogFile());

		$debug = new Debug($this->logDir, 'scriptFoo');
		$this->assertEquals($this->logDir . '/scriptFoo.log', $debug->getLogFile());

		$debug->setLogFile('foo');
		$this->assertEquals($this->logDir . '/foo.log', $debug->getLogFile());
	}

	public function testLogging() {
		$debug = new Debug($this->logDir);
		$logFile = $debug->getLogFile();

		$this->assertTrue($debug->log('test'));

		// remove log file
		$debug->clearLogFile();

		$this->assertFalse(file_exists($logFile));

		// log to a file
		$this->assertTrue($debug->log('foo'));

		$this->assertFileExists($logFile);
		$this->assertContains(": foo\n", file_get_contents($logFile));

		// log to a file
		$this->assertTrue($debug->log('bar'));

		$this->assertContains(": foo\n", file_get_contents($logFile));
		$this->assertContains(": bar\n", file_get_contents($logFile));
	}

	public function testLoggingEnableDisable() {
		$debug = new Debug($this->logDir);
		$logFile = $debug->getLogFile();

		// remove log file
		$debug->clearLogFile();

		$this->assertTrue($debug->log('test'));
		$this->assertContains(": test\n", file_get_contents($logFile));

		$debug->disableLog();
		$this->assertFalse($debug->log('foo'));
		$this->assertFalse(strpos(file_get_contents($logFile), 'foo'));

		$debug->enableLog();
		$this->assertTrue($debug->log('foo'));
		$this->assertContains(": foo\n", file_get_contents($logFile));
	}

	public function testLoggingThreshold() {
		$debug = new Debug($this->logDir);
		$logFile = $debug->getLogFile();

		// remove log file
		$debug->clearLogFile();

		$this->assertTrue($debug->log('test'));
		$this->assertContains(": test\n", file_get_contents($logFile));

		// set threshold to DEBUG level
		$debug->setLogThreshold(Debug::DEBUG);
		$this->assertFalse($debug->log('foo'));
		$this->assertFalse(strpos(file_get_contents($logFile), 'foo'));

		$this->assertTrue($debug->log('foo', Debug::DEBUG));
		$this->assertContains(": foo\n", file_get_contents($logFile));

		// set threshold to zero level (no logging)
		$debug->setLogThreshold(0);
		$this->assertFalse($debug->log('bar', Debug::ERROR));
		$this->assertFalse(strpos(file_get_contents($logFile), 'bar'));
	}
}