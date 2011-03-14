<?php

/**
 * Nano application's test module
 *
 * $Id$
 */

class FooModule extends Module {

	private function init() {}

	/**
	 * Default method used for routing requests for this module
	 */
	public function route($param) {
		return array('default' => true);
	}

	/**
	 * Method used for routing requests matching /foo/bar/*
	 */
	public function bar($id) {
		return array('id' => intval($id));
	}
}