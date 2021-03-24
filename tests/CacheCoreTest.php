<?php

/**
 * Set of generic unit tests for Cache
 */
abstract class CacheCoreTest extends CacheTest
{
    protected function getCacheInstance($settings = [])
    {
        return false;
    }

    public function testCacheFactory()
    {
        parent::testCacheFactory();
    }
}
