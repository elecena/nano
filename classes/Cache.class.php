<?php

namespace Nano;

/**
 * Abstract class for caching driver
 */

abstract class Cache {

	// key parts separator
	const SEPARATOR = '::';

	// debug
	protected $debug;

	// prefix for key names
	protected $prefix;

	// number of hits for cache keys
	protected $hits = 0;

	// number of misses for cache keys
	protected $misses = 0;

	/**
	 * Creates an instance of given cache driver
	 *
	 * @param array $settings
	 * @return Cache cache instance
	 */
	public static function factory(Array $settings) {
		$driver = isset($settings['driver']) ? $settings['driver'] : null;
		$className = sprintf('Nano\\Cache\\Cache%s', ucfirst($driver));

		return new $className($settings);
	}

	protected function __construct(Array $settings) {
		// use debugger from the application
		$app = \NanoApp::app();
		$this->debug = $app->getDebug();
		$this->debug->log("Cache: using '{$settings['driver']}' driver");

		// add performance report
		$events = $app->getEvents();
		$events->bind('NanoAppTearDown', array($this, 'onNanoAppTearDown'));

		// set prefix
		$this->prefix = isset($settings['prefix']) ? $settings['prefix'] : false;
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

		return str_replace(' ' , '_', $key);
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
	public function onNanoAppTearDown(\NanoApp $app) {
		$debug = $app->getDebug();

		$debug->log("Cache: {$this->hits} hits and {$this->misses} misses");

		// send stats
		$statsd = Stats::getCollector($app, 'cache');
		$statsd->count('hits', $this->getHits());
		$statsd->count('misses', $this->getMisses());
	}
}
