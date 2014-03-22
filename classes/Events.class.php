<?php

namespace Nano;

/**
 * Events handling
 */

class Events {

	const CALLBACK_SIMPLE = 1;
	const CALLBACK_CONTROLLER = 2;

	private $app;

	// list of events handlers
	private $events = array();

	public function __construct(\NanoApp $app) {
		$this->app = $app;
	}

	/**
	 * Binds given callback to be fired when given event occurs
	 *
	 * When can returns false, fire() method returns false too and no callbacks execution is stopped
	 */
	public function bind($eventName, $callback) {
		$this->events[$eventName][] = array(
			self::CALLBACK_SIMPLE,
			$callback
		);
	}

	/**
	 * Binds given controller's method to be fired when given event occurs
	 */
	public function bindController($eventName, $controllerName, $controllerMethod) {
		$callback = array(
			self::CALLBACK_CONTROLLER,
			array(
				$controllerName,
				$controllerMethod
			)
		);

		$this->events[$eventName][] = $callback;
	}

	/**
	 * Execute all callbacks binded to given event (passing additional parameters if provided)
	 */
	public function fire($eventName, $params = array()) {
		$callbacks = isset($this->events[$eventName]) ? $this->events[$eventName] : null;

		if ($callbacks) {
			foreach($callbacks as $entry) {
				list($type, $callback) = $entry;

				switch($type) {
					// lazy load of controllers
					case self::CALLBACK_CONTROLLER:
						list($name, $method) = $callback;

						$instance = $this->app->getController($name);
						$callback = array(
							$instance,
							$method
						);
						break;
				}

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