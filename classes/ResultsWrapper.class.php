<?php

/**
 * Wrapper for results
 *
 * $Id$
 */

class ResultsWrapper {

	// results
	protected $results;

	function __construct(Array $results = array()) {
		$this->results = $results;
	}

	/**
	 * Set given entry in results array
	 *
	 * Key should be lowercase
	 */
	public function set($key, $value) {
		$this->results[$key] = $value;
	}

	/**
	 * Get given results entry
	 *
	 * To get 'data' entry use $res->getData()
	 */
	public function __call($name, $parameters) {
		$res = null;

		// getXXX
		if (substr($name, 0, 3) == 'get') {
			$entry = strtolower(substr($name, 3));

			if (isset($this->results[$entry])) {
				$res = $this->results[$entry];
			}
		}

		return $res;
	}
}