<?php

/**
 * JSON with callback renderer
 */

class OutputJsonp extends Output {

	private $callbackFn = 'callback';

	/**
	 * Set callback's name
	 */
	public function setCallback($callback) {
		$this->callbackFn = $callback;
	}

	/**
	 * Render current data
	 */
	public function render() {
		return $this->callbackFn . '(' . json_encode($this->data) . ')';
	}

	/**
	 * @see http://www.ietf.org/rfc/rfc4329.txt
	 */
	public function getContentType() {
		return 'application/javascript; charset=UTF-8';
	}
}