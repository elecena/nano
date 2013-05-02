<?php

/**
 * JSON renderer
 */

class OutputJson extends Output {

	/**
	 * Render current data
	 */
	public function render() {
		return json_encode($this->data);
	}

	/**
	 * @see http://www.ietf.org/rfc/rfc4627.txt
	 */
	public function getContentType() {
		return 'application/json; charset=UTF-8';
	}
}