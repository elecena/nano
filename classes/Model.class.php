<?php

/**
 * Abstract class for representing nanoPortal's application model
 */

abstract class Model extends NanoObject {

	protected $data = array();

	/**
	 * When serializing model keep data only
	 *
	 * TODO: http://stackoverflow.com/a/4697671
	 *
	 * @see http://pl1.php.net/manual/pl/language.oop5.magic.php#object.sleep
	 *
	public function __sleep() {
		return array('data');
	}

	public function __toString() {
		return $this->getData();
	}
	**/

	/**
	 * Encodes a collection of models
	 */
	public static function toArray(Array $models) {
		$ret = array();

		foreach($models as $model) {
			$ret[] = $model->getData();
		}

		return $ret;
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