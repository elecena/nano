<?php

/**
 * Set of unit tests for Cache redis driver
 *
 * A locally running redis server is required
 */
class CacheRedisTest extends CacheTest
{

    /**
     * @param array $settings
     * @return \Nano\Cache
     */
    protected function getCacheInstance($settings = [])
    {
        $settings = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
        ], $settings);

        return $this->getCache('redis', $settings);
    }
}
