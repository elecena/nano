<?php

/**
 * Driver for caching using files
 */

namespace Nano\Cache;

use Nano\Cache;

class CacheFile extends Cache
{
    private $dir;

    /**
     * Creates an instance of cache driver
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $app = \NanoApp::app();
        $this->dir = isset($settings['directory']) ? $settings['directory'] : ($app->getDirectory() . '/cache');
    }

    /**
     * Gets key value
     */
    public function get($key, $default = null)
    {
        if ($this->exists($key)) {
            $data = file_get_contents($this->getFilePath($key));
            $value = $this->unserialize($data);

            $this->hits++;
        } else {
            $value = $default;

            $this->misses++;
        }

        return $value;
    }

    /**
     * Sets key value
     */
    public function set($key, $value, $ttl = null)
    {
        $file = $this->getFilePath($key);
        $data = $this->serialize($value);

        $ret = file_put_contents($file, $data);

        // set ttl
        if ($ret !== false && !is_null($ttl)) {
            $ret = touch($file, time() + $ttl);
        }

        return $ret;
    }

    /**
     * Checks if given key exists
     */
    public function exists($key)
    {
        return file_exists($this->getFilePath($key));
    }

    /**
     * Deletes given key
     */
    public function delete($key)
    {
        $path = $this->getFilePath($key);

        return file_exists($path) ? unlink($path) : true;
    }

    /**
     * Increase given key's value
     */
    public function incr($key, $by = 1)
    {
        $value = $this->get($key);

        // if the keys does not exist, it is set to 0 before incr happens
        $value = (is_null($value) ? 0 : intval($value)) + $by;
        $this->set($key, $value);

        return $value;
    }

    /**
     * Decrease given key's value
     */
    public function decr($key, $by = 1)
    {
        return $this->incr($key, -$by);
    }

    /**
     * Get path to the file used for storing data in the cache
     */
    private function getFilePath($key)
    {
        return $this->dir . '/' . md5($this->getStorageKey($key)) . '.cache';
    }
}
