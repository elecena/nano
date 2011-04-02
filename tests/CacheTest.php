<?php

/**
 * Set of unit tests for Cache class
 *
 * $Id$
 */

class CacheTest extends PHPUnit_Framework_TestCase {

	public function testCacheFactory() {
		$cache = Cache::factory('file', array(
			'directory' => '',
		));
		$this->assertInstanceOf('CacheFile', $cache);

		$cache = Cache::factory('FiLe', array(
			'directory' => '',
		));
		$this->assertInstanceOf('CacheFile', $cache);

		$cache = Cache::factory('Unknown');
		$this->assertNull($cache);
	}
}