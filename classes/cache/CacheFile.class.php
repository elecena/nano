<?php

/**
 * Driver for caching using files
 *
 * $Id$
 */

class CacheFile extends Cache {

	private $dir;

	/**
	 * Creates an instance of cache driver
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		$this->dir = $app->getConfig()->get('cache.directory',  $app->getDirectory() . '/cache');
	}

	/**
	 * Gets key value
	 */
	public function get($key, $default = null) {
		if ($this->exists($key)) {
			$data = file_get_contents($this->getFilePath($key));
			$value = $this->unserialize($data);
		}
		else {
			$value = $default;
		}

		return $value;
	}

	/**
	 * Sets key value
	 */
	public function set($key, $value, $ttl = null) {
		$file = $this->getFilePath($key);
		$data = $this->serialize($value);

		$ret = file_put_contents($file, $data);

		// set ttl
		if ($ret !== false && !is_null($ttl)) {
			$ret = touch($file, time() + $ttl);
		}

		return $ret;
	}

	/**
	 * Checks if given key exists
	 */
	public function exists($key) {
		return file_exists($this->getFilePath($key));
	}

	/**
	 * Deletes given key
	 */
	public function delete($key) {
		return unlink($this->getFilePath($key));
	}

	/**
	 * Increase given key's value
	 */
	public function incr($key, $by = 1) {
		$value = $this->get($key);

		if (!is_null($value)) {
			$value = intval($value) + $by;
			$this->set($key, $value);
		}

		return $value;
	}

	/**
	 * Decrease given key's value
	 */
	public function decr($key, $by = 1) {
		return $this->incr($key, -$by);
	}

	/**
	 * Get path to the file used for storing data in the cache
	 */
	private function getFilePath($key) {
		return $this->dir . '/' . md5($this->getStorageKey($key)) . '.cache';
	}
}