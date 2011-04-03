<?php

/**
 * Abstract class for output formatting
 *
 * $Id$
 */

abstract class Output {

	// data to be processed
	protected $data;

	/**
	 * Creates an instance of given cache driver
	 */
	public static function factory($driver, $data = null) {
		$className = 'Output' . ucfirst(strtolower($driver));

		$src = dirname(__FILE__) . '/output/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;

			$instance = new $className();

			if (!is_null($data)) {
				$instance->setData($data);
			}
		}
		else {
			$instance = null;
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
	 * Render current data
	 */
	abstract public function render();

	/**
	 * Get value of Content-type HTTP header suitable for given output formatter
	 */
	abstract public function getContentType();
}