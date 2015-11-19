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
class CacheRedis extends Cache {

	// Redis connection
	private $redis;

	/**
	 * Creates an instance of cache driver
	 *
	 * @see https://github.com/nrk/predis/wiki/Quick-tour#supported-connection-parameters
	 * @param array $settings
	 */
	public function __construct(Array $settings) {
		parent::__construct($settings);

		// read settings
		$host = isset($settings['host']) ? $settings['host'] : 'localhost';
		$port = isset($settings['port']) ? $settings['port'] : 6379;
		$timeout = isset($settings['timeout']) ? $settings['timeout'] : 5; // Predis default is 5 sec

		// lazy connect
		$this->redis = new Client([
			'scheme' => 'tcp',
			'host'   => $host,
			'port'   => $port,
			'timeout'=> $timeout,
		]);

		// TODO: introduce Nano\Benchmark::getTime and pass the code as a closure
		$then = microtime(true);
			$this->redis->connect();
		$now = microtime(true) - $then;

		$this->debug->log(__CLASS__ . ": using {$host}:{$port}");
		$this->logger->info("Connected to tcp://{$host}:{$port}", [
			'time' => $now * 1000
		]);
	}

	/**
	 * Gets key value
	 */
	public function get($key, $default = null) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->get($key);

		if ($resp !== null) {
			$value = $this->unserialize($resp);

			$this->hits++;
		}
		else {
			$value = $default;

			$this->misses++;
		}

		#$this->debug->log(__METHOD__ . ": {$key}");
		return $value;
	}

	/**
	 * Sets key value
	 */
	public function set($key, $value, $ttl = null) {
		$key = $this->getStorageKey($key);
		$this->redis->set($key, $this->serialize($value));

		if (!is_null($ttl)) {
			$this->redis->expire($key, $ttl);
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
	public function exists($key) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->exists($key);

		return $resp === true;
	}

	/**
	 * Deletes given key
	 *
	 * @param $key mixed|string
	 * @return bool
	 */
	public function delete($key) {
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
	public function incr($key, $by = 1) {
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
	public function decr($key, $by = 1) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->decrby($key, $by);

		return $resp;
	}
}
