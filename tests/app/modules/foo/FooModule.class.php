<?php

/**
 * Nano application's test module
 *
 * $Id$
 */

class FooModule extends Module {

	/**
	 * Default method used for routing requests for this module
	 */
	public function route($param) {

	}

	/**
	 * Method used for routing requests matching /foo/bar/*
	 */
	public function bar($id) {
		return array('id' => intval($id));
	}
}