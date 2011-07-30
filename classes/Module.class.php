<?php

/**
 * Abstract class for representing nanoPortal's modules
 *
 * $Id$
 */

abstract class Module {
	// application
	protected $app;

	// module's directory
	protected $dir;

	// cache object
	protected $cache;

	// debug
	protected $debug;

	// DB connection
	protected $database;

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

	// output's format
	protected $format;

	/**
	 * Setup the module usin ggiven application
	 */
	protected function __construct(NanoApp $app, $name) {
		$this->name = $name;

		// set protected fields
		$this->app = $app;
		$this->cache = $app->getCache();
		$this->config = $app->getConfig();
		$this->database = $app->getDatabase();
		$this->debug = $app->getDebug();
		$this->events = $app->getEvents();
		$this->request = $app->getRequest();
		$this->response = $app->getResponse();
		$this->router = $app->getRouter();
	}

	/**
	 * Create and setup instance of given module for given application
	 */
	public static function factory(NanoApp $app, $moduleName) {
		$className = $moduleName . 'Module';

		// request given file
		$dir = $app->getDirectory() . '/modules/' . strtolower($moduleName);
		$src = $dir . '/' . $className . '.class.php';

		if (file_exists($src)) {
			require_once $src;
			$instance = new $className($app, $moduleName);
			$instance->dir = $dir;
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
	 * Get module's directory
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Set output's format
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * Get output's format
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Binds given module's method to be fired when given event occurs
	 *
	 * When can false is returned, fire() method returns false too and no callbacks execution is stopped
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