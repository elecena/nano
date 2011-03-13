<?php

/**
 * Abstract class for caching driver
 *
 * $Id$
 */

abstract class Cache {

	// number of hits for cache keys
	private $hits = 0;

	// number of misses for cache keys
	private $misses = 0;

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory($driver, Array $options = array()) {
		$className = 'Cache' . ucfirst($driver);

		// use autoloader to add requested class
		Autoloader::add($className, dirname(__FILE__) . '/cache/' . $className . '.class.php');

		$instance = new $className($options);
		return $instance;
	}

	/**
	 * Gets key value
	 */
	abstract public function get($key, $default = null);

	/**
	 * Sets key value
	 */
	abstract public function set($key, $value, $ttl);

	/**
	 * Checks if given key exists
	 */
	abstract public function exists($key);

	/**
	 * Deletes given key
	 */
	abstract public function delete($key);

	/**
	 * Serialize data before storing in the cache
	 */
	protected function serialize($data) {
		#return json_encode($data);
		return serialize($data);
	}

	/**
	 * Unserialize data after returning them from the cache
	 */
	protected function unserialize($data) {
		#return json_decode($data, true /* as array */);
		return unserialize($data);
	}

	/**
	 * Get key used for storing in the cache
	 */
	protected function getKey($key) {
		// merge key passed as an array
		if (is_array($key)) {
			$key = implode('::', $key);
		}

		return $key;
	}
}