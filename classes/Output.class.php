<?php

/**
 * Abstract class for output formatting
 */

abstract class Output {

	// data to be processed
	protected $data;

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory($driver, $data = null) {
		$instance = Autoloader::factory('Output', $driver, dirname(__FILE__) . '/output');

		if (!is_null($instance)) {
			if (!is_null($data)) {
				$instance->setData($data);
			}
		}

		return $instance;
	}

	/**
	 * Set data to be formatted
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * Return raw data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Render current data
	 *
	 * @return string
	 */
	abstract public function render();

	/**
	 * Get value of Content-type HTTP header suitable for given output formatter
	 */
	abstract public function getContentType();
}