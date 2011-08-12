<?php

/**
 * JSON with callback renderer
 *
 * $Id$
 */

class OutputJsonp extends Output {

	private $callback = 'callback';

	/**
	 * Set callback's name
	 */
	public function setCallback($callback) {
		$this->callback = $callback;
	}

	/**
	 * Render current data
	 */
	public function render() {
		return $this->callback . '(' . json_encode($this->data) . ')';
	}

	/**
	 * @see http://www.ietf.org/rfc/rfc4329.txt
	 */
	public function getContentType() {
		return 'application/javascript';
	}
}