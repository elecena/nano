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
	 * Force constructors to be protected - use Cache::factory
	 */
	abstract protected function __construct(Array $options);

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory($driver, Array $options = array()) {
		$className = 'Cache' . ucfirst(strtolower($driver));

		$src = dirname(__FILE__) . '/cache/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;

			$instance = new $className($options);
		}
		else {
			$instance = null;
		}
		return $instance;
	}

	/**
	 * Gets key value
	 */
	abstract public function get($key, $default = null);

	/**
	 * Sets key value
	 */
	abstract public function set($key, $value, $ttl = null);

	/**
	 * Checks if given key exists
	 */
	abstract public function exists($key);

	/**
	 * Deletes given key
	 */
	abstract public function delete($key);

	/**
	 * Increase given key's value and returns updated value
	 */
	abstract public function incr($key, $by = 1);

	/**
	 * Decrease given key's value and returns updated value
	 */
	abstract public function decr($key, $by = 1);

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
	protected function getStorageKey($key) {
		// merge key passed as an array
		if (is_array($key)) {
			$key = implode('::', $key);
		}

		return $key;
	}
}