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
	function __construct(Array $options = array()) {
		$this->dir = $options['directory'];
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
	public function set($key, $value, $ttl) {
		$file = $this->getFilePath($key);
		$data = $this->serialize($value);

		$ret = file_put_contents($file, $data);

		// set ttl
		if ($ret !== false) {
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
	 * Get path to the file used for storing data in the cache
	 */
	private function getFilePath($key) {
		return $this->dir . '/' . md5($this->getKey($key)) . '.cache';
	}
}