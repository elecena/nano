<?php

/**
 * Abstract class for representing nanoPortal's modules
 *
 * $Id$
 */

abstract class Module {
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

	// module's name
	private $name;

	/**
	 * Use given application
	 */
	private function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Perform initialization tasks
	 */
	private function init() {}

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

			// set private fields
			$instance->cache = $app->getCache();
			$instance->router = $app->getRouter();
			$instance->config = $app->getConfig();

			$instance->init();
		}
		else {
			$instance = null;
		}

		return $instance;
	}
}