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

	private function getCacheFile() {
		$dir = dirname(__FILE__) . '/app/cache';

		$cache = Cache::factory('file', array(
			'directory' => $dir
		));

		return $cache;
	}

	public function testCacheGetSet() {
		$cache = $this->getCacheFile();
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

	public function testCacheIncrDecr() {
		$cache = $this->getCacheFile();
		$this->assertInstanceOf('CacheFile', $cache);

		$key = 'bar';
		$value = 12;

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->incr($key));
		$this->assertNull($cache->decr($key));

		$cache->set($key, $value, 60);

		$this->assertEquals($value, $cache->get($key));
		$this->assertEquals($value + 1, $cache->incr($key));
		$this->assertEquals($value + 5, $cache->incr($key, 4));
		$this->assertEquals($value + 5, $cache->get($key));

		$this->assertEquals($value + 4, $cache->decr($key));
		$this->assertEquals($value, $cache->decr($key, 4));
		$this->assertEquals($value, $cache->get($key));

		$cache->delete($key);

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->incr($key));
		$this->assertNull($cache->decr($key));
	}
}