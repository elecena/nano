<?php

/**
 * Abstract class for representing nanoPortal's modules
 *
 * $Id$
 */

abstract class Module {
	// application
	protected $app;

	// cache object
	protected $cache;

	// DB connection
	protected $db;

	// HTTP request
	protected $request;

	// response
	protected $response;

	// router
	protected $router;

	// config
	protected $config;

	// events handler
	protected $events;

	// module's name
	protected $name;

	/**
	 * Use given application
	 */
	private function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Perform initialization tasks
	 */
	protected function init() {}

	/**
	 * Create and setup instance of given module for given application
	 */
	public static function factory($moduleName, NanoApp $app) {
		$className = $moduleName . 'Module';

		// request given file
		$dir = $app->getDirectory() . '/modules/' . strtolower($moduleName);
		$src = $dir . '/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;

			$instance = new $className($moduleName);

			// set protected fields
			$instance->app = $app;
			$instance->cache = $app->getCache();
			$instance->config = $app->getConfig();
			$instance->events = $app->getEvents();
			$instance->request = $app->getRequest();
			$instance->response = $app->getResponse();
			$instance->router = $app->getRouter();

			$instance->init();
		}
		else {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Use provided request
	 */
	public function setRequest(Request $request) {
		$this->request = $request;
	}

	/**
	 * Binds given module's method to be fired when given event occurs
	 *
	 * When can returns false, fire() method returns false too and no callbacks execution is stopped
	 */
	protected function bind($eventName, $callbackMethod) {
		$this->events->bind($eventName, array($this, $callbackMethod));
	}

	/**
	 * Execute all callbacks binded to given event (passing additional parameters if provided)
	 */
	protected function fire($eventName, $params = array()) {
		return $this->events->fire($eventName, $params);
	}
}