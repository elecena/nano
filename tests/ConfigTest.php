<?php

/**
 * Set of unit tests for Config class
 */

use Nano\Config;

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
		$this->assertEquals('bar', $config->get($key, 'bar'));

		$config->set($key, $val);

		$this->assertEquals($val, $config->get($key));
		$this->assertEquals($val, $config->get($key, 'bar'));

		$config->delete($key, $val);

		$this->assertNull($config->get($key));
		$this->assertEquals('foo', $config->get($key, 'foo'));

		$config->set($key, array(
			'foo' => 'bar',
			'test' => true,
		));

		$this->assertEquals(array(
			'abc' => 123,
			'foo' => 'bar',
			'test' => true,
		), $config->get($key, array(
			'abc' => 123,
			'test' => false,
		)));
	}
}