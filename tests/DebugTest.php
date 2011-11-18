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

		$this->assertFileNotExists($logFile);

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
		$this->assertNotContains('foo', file_get_contents($logFile));

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
		$this->assertNotContains('foo', file_get_contents($logFile));

		$this->assertTrue($debug->log('foo', Debug::DEBUG));
		$this->assertContains(": foo\n", file_get_contents($logFile));

		// set threshold to zero level (no logging)
		$debug->setLogThreshold(0);
		$this->assertFalse($debug->log('bar', Debug::ERROR));
		$this->assertNotContains('bar', file_get_contents($logFile));
	}

	public function testTimers() {
		$debug = new Debug($this->logDir);

		$this->assertNull($debug->timeEnd('foo'));

		// start timers
		$debug->time('foo');
		usleep(100000); // 100 ms
		$debug->time('bar');
		usleep(100000); // 100 ms

		// get rimers
		$timerFoo = $debug->timeEnd('foo');
		$timerBar = $debug->timeEnd('bar');

		$this->assertTrue($timerFoo > $timerBar);
		#$this->assertTrue($timerFoo > 0.2);
		#$this->assertTrue($timerBar > 0.1);

		$this->assertNull($debug->timeEnd('foo'));
		$this->assertNull($debug->timeEnd('bar'));
	}
}