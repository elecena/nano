<?php

namespace Nano\Cache;

use Nano\Cache;

use Predis\Client;

/**
 * Driver for caching using Redis key-value persistent DB
 *
 * Requires predis library to be installed
 *
 * @see https://packagist.org/packages/predis/predis
 */
class CacheRedis extends Cache
{
    // Redis connection
    private $redis;

    /**
     * Creates an instance of cache driver
     *
     * @see https://github.com/nrk/predis/wiki/Quick-tour#supported-connection-parameters
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        // read settings
        $host = $settings['host'] ?? 'localhost';
        $port = $settings['port'] ?? 6379;
        $password = $settings['password'] ?? false;
        $timeout = $settings['timeout'] ?? 5; // Predis default is 5 sec

        // lazy connect
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
            'password' => $password,
            'timeout'=> $timeout,
            'persistent' => !empty($settings['persistent']),
        ]);
    }

    /**
     * Gets key value
     *
     * @param string|array $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $key = $this->getStorageKey($key);
        $resp = $this->redis->get($key);

        if ($resp !== null) {
            $value = $this->unserialize($resp);

            $this->hits++;
        } else {
            $value = $default;

            $this->misses++;
        }

        #$this->debug->log(__METHOD__ . ": {$key}");
        return $value;
    }

    /**
     * Sets key value
     *
     * @param string|array $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->getStorageKey($key);

        if (is_int($ttl)) {
            # @see http://redis.io/commands/set (EX's ttl supported since v2.6.12)
            $this->redis->set($key, $this->serialize($value), 'EX', $ttl);
        } else {
            # cache infinitly
            $this->redis->set($key, $this->serialize($value));
        }

        #$this->debug->log(__METHOD__ . ": {$key}");

        // Status code reply: always OK since SET can't fail.
        return true;
    }

    /**
     * Checks if given key exists
     *
     * @param $key mixed|string
     * @return bool
     */
    public function exists($key)
    {
        $key = $this->getStorageKey($key);
        $resp = $this->redis->exists($key);

        return $resp === 1;
    }

    /**
     * Deletes given key
     *
     * @param $key mixed|string
     * @return bool
     */
    public function delete($key)
    {
        $key = $this->getStorageKey($key);
        $this->redis->del($key);

        // Return value - Integer reply: The number of keys that were removed.
        return true;
    }

    /**
     * Increase given key's value and returns updated value
     *
     * @param $key mixed|string
     * @param int $by
     * @return int new value
     */
    public function incr($key, $by = 1)
    {
        $key = $this->getStorageKey($key);
        $resp = $this->redis->incrby($key, $by);

        return $resp;
    }

    /**
     * Decrease given key's value and returns updated value
     *
     * @param $key mixed|string
     * @param int $by
     * @return int new value
     */
    public function decr($key, $by = 1)
    {
        $key = $this->getStorageKey($key);
        $resp = $this->redis->decrby($key, $by);

        return $resp;
    }
}
