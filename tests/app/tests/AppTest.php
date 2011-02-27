<?php

/**
 * Set of unit tests for Nano's Application class
 *
 * $Id$
 */

class AppTest extends PHPUnit_Framework_TestCase {

	public function testCreateApp() {
		$dir = realpath(dirname(__FILE__) . '/..');
		$config = array();

		$app = Nano::app($dir, $config);

		$this->assertInstanceOf('NanoApp', $app);
		$this->assertEquals($dir, $app->getDirectory());
	}
}