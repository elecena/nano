<?php

/**
 * Nano application's test module
 *
 * $Id$
 */

class FooModule extends Module {

	protected function __construct(NanoApp $app, $name) {
		parent::__construct($app, $name);

		// bind to "eventFoo" event
		$this->bind('eventFoo', 'onFoo');
	}

	/**
	 * Default method used for routing requests for this module
	 */
	public function route($param) {
		return '';
	}

	/**
	 * Method used for routing requests matching /foo/bar/*
	 */
	public function bar($id) {
		return array('id' => intval($id));
	}

	/**
	 * Method for testing events firing
	 */
	public function event($var) {
		$this->fire('eventFoo', array(&$var));

		return $var;
	}

	/**
	 * This method can not be routed
	 */
	public function _test($id) {
		return array('test' => $id);
	}

	/**
	 * Event handler
	 */
	public function onFoo($value) {
		$value .= 'test';
	}
}