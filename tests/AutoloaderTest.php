<?php

/**
 * Set of unit tests for Autoloader class
 *
 * $Id$
 */

class AutoloaderTest extends PHPUnit_Framework_TestCase {

	public function testAdd() {
		$this->assertFalse(class_exists('Example'));

		// register Example class
		Autoloader::add('Example', dirname(__FILE__) . '/Example.class.php');

		$this->assertTrue(class_exists('Example'));
		$this->assertEquals('bar', Example::$foo);
	}
}