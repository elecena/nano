<?php

/**
 * Set of unit tests for Cache file driver
 */

class CacheFileTest extends CacheTest
{
    protected function getCacheInstance($settings = [])
    {
        $dir = dirname(__FILE__) . '/app/cache';

        $settings = array_merge([
            'directory' => $dir,
        ], $settings);

        return $this->getCache('file', $settings);
    }
}
