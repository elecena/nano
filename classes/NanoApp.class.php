<?php

/**
 * Class for representing nanoPortal's application
 *
 * $Id$
 */

class NanoApp {
	// cache object
	private $cache;

	// DB connection
	private $db;

	// HTTP request
	private $request;

	// router
	private $router;

	// config
	private $config;

	// application's working directory
	private $dir;

	/**
	 * Create application based on given config
	 */
	function __construct($dir, $configSet = 'default') {
		$this->dir = realpath($dir);

		// register classes from /classes directory
		Autoloader::scanDirectory($this->dir. '/classes');

		// read configuration
		$this->config = new Config($this->dir . '/config');
		$this->config->load($configSet);

		// setup cache
		$cacheType = $this->config->get('cache.driver', 'file');
		$cacheOptions = $this->config->get('cache.options', array(
			'directory' => $this->dir . '/cache'
		));

		$this->cache = Cache::factory($cacheType, $cacheOptions);

		// TODO: set request and connection to database

		// set private fields
		$this->router = new Router($this);
	}

	/**
	 * Returns instance of given class from /classes directory
	 *
	 * Class constructor is called with application's instance
	 */
	public function factory($className) {
		if (class_exists($className)) {
			$instance = new $className($this);
		}
		else {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Route given request
	 */
	public function route(Request $request) {
		// load all modules

		// route given request
		$this->router->route($request);
	}

	/**
	 * Create and setup instance of given module for given application
	 */
	public function getModule($moduleName) {
		$instance = Module::factory($moduleName, $this);

		return $instance;
	}

	/**
	 * Return path to application
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Return cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Return router
	 */
	public function getRouter() {
		return $this->router;
	}

	/**
	 * Return config
	 */
	public function getConfig() {
		return $this->config;
	}
}