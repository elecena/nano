<?php

/**
 * Abstract class for representing nanoPortal's application model
 *
 * $Id$
 */

abstract class Model extends NanoObject {

	protected $data = array();

	function __construct(NanoApp $app) {
		parent::__construct($app);
	}

	/**
	 * Get given data entry
	 *
	 * Example: $model->getName() returns $model->data['name']
	 */
	public function __call($name, $parameters) {
		$res = null;

		// getXXX()
		if (substr($name, 0, 3) == 'get') {
			$entry = strtolower(substr($name, 3));

			if (isset($this->data[$entry])) {
				$res = $this->data[$entry];
			}
		}

		return $res;
	}

	/**
	 * Get model data
	 */
	public function getData() {
		return $this->data;
	}
}