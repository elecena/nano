<?php

/**
 * Set of generic unit tests for Cache
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/CacheTest.php');
 
class CacheCoreTest extends CacheTest {

	public function testCacheFactory() {
		$this->assertInstanceOf('CacheFile', $this->getCache('file'));
		$this->assertInstanceOf('CacheFile', $this->getCache('FiLe'));
		$this->assertInstanceOf('CacheRedis', $this->getCache('redis', array('host' => '127.0.0.1')));
		$this->assertNull($this->getCache('Unknown'));
	}
}