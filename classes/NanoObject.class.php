<?php

namespace Nano;

/**
 * Abstract class for representing nanoPortal's application models and services
 */

abstract class NanoObject {

	// application
	protected $app;

	// cache object
	protected $cache;

	// debug
	protected $debug;

	// DB connection
	protected $database;

	// config
	protected $config;

	// events handler
	protected $events;

	/**
	 * Use given application
	 */
	function __construct() {
		$this->app = \NanoApp::app();

		$this->cache = $this->app->getCache();
		$this->config = $this->app->getConfig();
		$this->debug = $this->app->getDebug();
		$this->events = $this->app->getEvents();

		// use lazy-resolving
		$this->database = static::getDatabase($this->app);
	}
	
	/**
	 * Allow models and services to use different database
	 */
	static protected function getDatabase(\NanoApp $app) {
		return $app->getDatabase();
	}
}
