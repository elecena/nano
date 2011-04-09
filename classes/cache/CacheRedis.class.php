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
	protected function __construct(Array $options = array()) {
		$this->ip = $options['ip'];
		$this->port = isset($options['port']) ? $options['port'] : 0;
		$this->pass = isset($options['pass']) ? $options['pass'] : null;
		$this->db = isset($options['db']) ? $options['db'] : 0;
	}

	/**
	 * Gets key value
	 */
	public function get($key, $default = null) {}

	/**
	 * Sets key value
	 */
	public function set($key, $value, $ttl) {}

	/**
	 * Checks if given key exists
	 */
	public function exists($key) {}

	/**
	 * Deletes given key
	 */
	public function delete($key) {}
}