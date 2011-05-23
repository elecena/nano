<?php

/**
 * Set of unit tests for Cache class
 *
 * $Id$
 */

class CacheTest extends PHPUnit_Framework_TestCase {

	private function getCache($driver = 'file', $settings = array()) {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$app = Nano::app($dir);

		$settings = array_merge(array(
			'driver' => $driver,
		), $settings);

		return Cache::factory($app, $settings);
	}

	public function testCacheFactory() {
		$this->assertInstanceOf('CacheFile', $this->getCache('file'));
		$this->assertInstanceOf('CacheFile', $this->getCache('FiLe'));
		$this->assertInstanceOf('CacheRedis', $this->getCache('redis', array('ip' => '127.0.0.1')));
		$this->assertNull($this->getCache('Unknown'));
	}

	private function getCacheFile() {
		$dir = dirname(__FILE__) . '/app/cache';

		return $this->getCache('file', array('directory' => $dir));
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