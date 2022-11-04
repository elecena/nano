<?php

use Nano\Cache;

/**
 * Set of unit tests for Cache redis driver
 *
 * A locally running redis server is required
 */
class CacheRedisTest extends CacheTest
{
    /**
     * @param array $settings
     * @return Cache
     * @throws Exception
     */
    protected function getCacheInstance(array $settings = []): Cache
    {
        $settings = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
        ], $settings);

        return $this->getCache('redis', $settings);
    }

    /**
     * @throws Exception
     */
    public function testSetCacheInfinity()
    {
        $cache = $this->getCacheInstance();

        $key = 'foo';
        $value = 'bar';

        $this->assertTrue($cache->set($key, $value));
        $this->assertTrue($cache->exists($key));
        $this->assertEquals($value, $cache->get($key));
    }
}
