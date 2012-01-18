<?php

/**
 * Abstract class for caching driver
 *
 * $Id$
 */

abstract class Cache {

	// key parts separator
	const SEPARATOR = '::';

	// debug
	protected $debug;

	// prefix for key names
	protected $prefix;

	// number of hits for cache keys
	private $hits = 0;

	// number of misses for cache keys
	private $misses = 0;

	/**
	 * Force constructors to be protected - use Cache::factory
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		// use debugger from the application
		$this->debug = $app->getDebug();

		// add performance report
		$events = $app->getEvents();
		$events->bind('NanoAppTearDown', array($this, 'onNanoAppTearDown'));

		// set prefix
		$this->prefix = isset($settings['prefix']) ? $settings['prefix'] : false;
	}

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory(NanoApp $app, Array $settings) {

		$driver = isset($settings['driver']) ? $settings['driver'] : null;
		$instance = null;

		if (!empty($driver)) {
			$className = 'Cache' . ucfirst(strtolower($driver));

			$src = dirname(__FILE__) . '/cache/' . $className . '.class.php';

			if (file_exists($src)) {
				require_once $src;

				$instance = new $className($app, $settings);
			}
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
	 * Increases given key's value and returns updated value
	 */
	abstract public function incr($key, $by = 1);

	/**
	 * Decreases given key's value and returns updated value
	 */
	abstract public function decr($key, $by = 1);

	/**
	 * Serialize data before storing in the cache
	 */
	protected function serialize($data) {
		return json_encode($data);
	}

	/**
	 * Unserialize data after returning them from the cache
	 */
	protected function unserialize($data) {
		return json_decode($data, true /* as array */);
	}

	/**
	 * Get key used for storing in the cache
	 */
	protected function getStorageKey($key) {
		// merge key passed as an array
		if (is_array($key)) {
			$key = implode(self::SEPARATOR, $key);
		}

		// add prefix (if provided)
		if ($this->prefix !== false) {
			$key = $this->prefix . self::SEPARATOR . $key;
		}

		return $key;
	}

	/**
	 * Get number of cache hits
	 */
	public function getHits() {
		return $this->hits;
	}

	/**
	 * Get number of cache misses
	 */
	public function getMisses() {
		return $this->misses;
	}

	/**
	 * Add performance report to the log
	 */
	public function onNanoAppTearDown(NanoApp $app) {
		$debug = $app->getDebug();
		$response = $app->getResponse();

		$debug->log("Cache: {$this->hits} hits and {$this->misses} misses");
	}
}