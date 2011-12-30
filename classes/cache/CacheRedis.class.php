<?php

/**
 * Driver for caching using Redis key-value persistent DB
 *
 * $Id$
 */

class CacheRedis extends Cache {

	// Redis connection
	private $redis;

	/**
	 * Creates an instance of cache driver
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		// load php-redis library
		Nano::addLibrary('php-redis');
		Autoloader::add('Redis', 'Redis.php');

		// read settings
		$host = isset($settings['host']) ? $settings['host'] : 'localhost';
		$port = isset($settings['port']) ? $settings['port'] : 6379;
		$pass = isset($settings['pass']) ? $settings['pass'] : false;

		// lazy connect
		$this->redis = new Redis($host, $port);
		#$this->redis->debug = true;

		// authenticate (if required)
		if ($pass !== false) {
			$this->redis->auth($pass);
		}
	}

	/**
	 * Gets key value
	 */
	public function get($key, $default = null) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->get($key);

		return $resp === null ? $default : $this->unserialize($resp);
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

		// Status code reply: always OK since SET can't fail.
		return true;
	}

	/**
	 * Checks if given key exists
	 */
	public function exists($key) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->exists($key);

		// @see http://redis.io/commands/exists
		return $resp === 1;
	}

	/**
	 * Deletes given key
	 */
	public function delete($key) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->delete($key);

		// Return value - Integer reply: The number of keys that were removed.
		return true;
	}

	/**
	 * Increase given key's value and returns updated value
	 */
	public function incr($key, $by = 1) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->incr($key, $by);

		return $resp;
	}

	/**
	 * Decrease given key's value and returns updated value
	 */
	public function decr($key, $by = 1) {
		$key = $this->getStorageKey($key);
		$resp = $this->redis->decr($key, $by);

		return $resp;
	}
}