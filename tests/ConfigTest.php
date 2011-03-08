<?php

/**
 * Set of unit tests for Config class
 *
 * $Id$
 */

class ConfigTest extends PHPUnit_Framework_TestCase {

	public function testGetDirectory() {
		$dir = dirname(__FILE__);

		$config = new Config($dir);

		$this->assertEquals($dir, $config->getDirectory());
	}

	public function testGetSetDelete() {
		$dir = dirname(__FILE__);
		$key = 'foo.bar';
		$val = 'test';

		$config = new Config($dir);

		$this->assertNull($config->get($key));

		$config->set($key, $val);

		$this->assertEquals($val, $config->get($key));

		$config->delete($key, $val);

		$this->assertNull($config->get($key));
	}
}