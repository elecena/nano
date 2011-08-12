<?php

/**
 * Driver for caching using Redis key-value persistent DB
 *
 * $Id$
 */

class CacheRedis extends Cache {

	// Redis server IP
	private $ip;

	// Redis server port
	private $port;

	// Redis authentication password
	private $pass;

	// Redis selected database
	private $db;

	/**
	 * Creates an instance of cache driver
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		$this->ip = $settings['ip'];
		$this->port = isset($settings['port']) ? $settings['port'] : 0;
		$this->pass = isset($settings['pass']) ? $settings['pass'] : null;
		$this->db = isset($settings['db']) ? $settings['db'] : 0;
	}

	/**
	 * Gets key value
	 */
	public function get($key, $default = null) {}

	/**
	 * Sets key value
	 */
	public function set($key, $value, $ttl = null) {}

	/**
	 * Checks if given key exists
	 */
	public function exists($key) {}

	/**
	 * Deletes given key
	 */
	public function delete($key) {}

	/**
	 * Increase given key's value and returns updated value
	 */
	public function incr($key, $by = 1) {}

	/**
	 * Decrease given key's value and returns updated value
	 */
	public function decr($key, $by = 1) {}
}