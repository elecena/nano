<?php

/**
 * Nano application's test controller
 *
 * $Id$
 */

class FooController extends Controller {

	protected function __construct(NanoApp $app, $name) {
		parent::__construct($app, $name);

		// bind to "eventFoo" event
		$this->bind('eventFoo', 'onFoo');
	}

	/**
	 * Default method used for routing requests for this controller
	 */
	public function route($param) {
		return false;
	}

	/**
	 * Method used for routing requests matching /foo/bar/*
	 */
	public function bar($id) {
		$this->id = intval($id);
	}

	/**
	 * Method used for routing requests matching /foo/bar/search?q=*
	 */
	public function search() {
		$this->isInternal = $this->request->isInternal();
		$this->set('query', $this->request->get('q', '')); // null values are not passed to the controller's data
	}

	/**
	 * Method used for routing requests matching /foo/json/*
	 */
	public function json($id) {
		$this->setData(array(
			'id' => intval($id)
		));
		$this->setFormat('json');
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