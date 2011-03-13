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
	 * Create and setup instance of given module for given application
	 */
	public static function factory($moduleName, NanoApp $app) {
		$className = $moduleName . 'Module';

		// request given file
		$dir = $app->getDirectory() . '/modules/' . strtolower($moduleName);
		$file = $dir . '/' . $className . '.class.php';

		if (file_exists($file)) {
			require_once $file;

			$instance = new $className($moduleName);

			// set private fields
			$instance->cache = $app->getCache();
			$instance->router = $app->getRouter();
			$instance->config = $app->getConfig();
		}
		else {
			$instance = null;
		}

		return $instance;
	}
}