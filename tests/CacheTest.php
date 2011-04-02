<?php

/**
 * Set of unit tests for Cache class
 *
 * $Id$
 */

class CacheTest extends PHPUnit_Framework_TestCase {

	public function testCacheFactory() {
		$cache = Cache::factory('file');
		$this->assertInstanceOf('CacheFile', $cache);

		$cache = Cache::factory('FiLe');
		$this->assertInstanceOf('CacheFile', $cache);

		$cache = Cache::factory('Unknown');
		$this->assertNull($cache);
	}

	public function testCacheGetSet() {
		$dir = dirname(__FILE__) . '/app/cache';

		$cache = Cache::factory('file', array(
			'directory' => $dir
		));
		$this->assertInstanceOf('CacheFile', $cache);

		$key = 'foo';
		$value = array(
			'test' => true,
			'mixed' => array(1,2,3),
			'pi' => 3.1415
		);

		$this->assertTrue($cache->set($key, $value, 60));
		$this->assertTrue($cache->exists($key));
		$this->assertEquals($value, $cache->get($key));

		$cache->delete($key);

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));

		// mixed keys
		$key = array('foo', 'bar', 'test');

		$this->assertTrue($cache->set($key, $value, 60));
		$this->assertTrue($cache->exists($key));
		$this->assertEquals($value, $cache->get($key));

		$cache->delete($key);

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));

		// handling of non-existing keys
		$key = 'notExistingKey';

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));
		$this->assertEquals($value, $cache->get($key, $value));
	}
}