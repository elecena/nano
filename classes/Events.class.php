<?php

/**
 * Events handling
 *
 * $Id$
 */

class Events {

	// list of events handlers
	private $events = array();

	/**
	 * Binds given callback to be fired when given event occurs
	 *
	 * When can returns false, fire() method returns false too and no callbacks execution is stopped
	 */
	public function bind($eventName, $callback) {
		$this->events[$eventName][] = $callback;
	}

	/**
	 * Execute all callbacks binded to given event (passing additional parameters if provided)
	 */
	public function fire($eventName, $params = array()) {
		$callbacks = isset($this->events[$eventName]) ? $this->events[$eventName] : null;

		if ($callbacks) {
			foreach($callbacks as $callback) {
				$ret = call_user_func_array($callback, $params);

				// stop further callbacks' execution
				if ($ret === false) {
					return false;
				}
			}
		}

		return true;
	}
}