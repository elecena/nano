<?php

/**
 * Set of generic unit tests for Cache
 */
class CacheCoreTest extends CacheTest {

	protected function getCacheInstance($settings = array()) {
		return false;
	}

	public function testCacheFactory() {
		parent::testCacheFactory();
	}
}
