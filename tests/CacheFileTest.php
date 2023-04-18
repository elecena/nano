<?php

use Nano\Cache;

/**
 * Set of unit tests for Cache file driver
 */

class CacheFileTest extends CacheTestBase
{
    protected function getCacheInstance(array $settings = []): Cache
    {
        $dir = __DIR__ . '/app/cache';

        $settings = array_merge([
            'directory' => $dir,
        ], $settings);

        return $this->getCache('file', $settings);
    }
}
