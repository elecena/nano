<?php

/**
 * Abstract class for caching driver
 *
 * $Id$
 */

class Cache {

	// number of hits for cache keys
	private $hits = 0;

	// number of misses for cache keys
	private $misses = 0;

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory($driver, Array $options = array()) {
		$className = 'Cache' . ucfirst($driver);
		$instance = new $className($options);

		return $instance;
	}

	/**
	 * Gets key value
	 */
	abstract public function get($key) {}

	/**
	 * Sets key value
	 */
	abstract public function set($key, $value, $ttl) {}

	/**
	 * Checks if given key exists
	 */
	abstract public function exists($key) {}

	/**
	 * Removes given key
	 */
	abstract public function remove($key) {}
}