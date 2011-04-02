<?php

/**
 * JSON renderer
 *
 * $Id$
 */

class OutputJson extends Output {

	/**
	 * Render current data
	 */
	public function render() {
		return json_encode($this->data);
	}
}