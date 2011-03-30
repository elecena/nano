<?php

/**
 * Nano application's test module
 *
 * $Id$
 */

class FooModule extends Module {

	protected function init() {
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
	 * This method can not be routed
	 */
	public function _test($id) {
		return array('test' => $id);
	}

	/**
	 * Method used for routing requests matching /foo/bar/*
	 */
	public function apiBar($id) {
		$query = $this->request->get('q', 'foo');

		return array('id' => intval($id), 'api' => true, 'query' => $query);
	}

	/**
	 * Event handler
	 */
	public function onFoo($value) {
		$value .= 'test';
	}
}