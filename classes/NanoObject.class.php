<?php

/**
 * Abstract class for representing nanoPortal's application models and services
 *
 * $Id$
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
	function __construct(NanoApp $app) {
		$this->app = $app;

		$this->cache = $app->getCache();
		$this->config = $app->getConfig();
		$this->debug = $app->getDebug();
		$this->events = $app->getEvents();

		// use lazy-resolving
		$this->database = static::getDatabase($app);
	}
	
	/**
	 * Allow models and services to use different database
	 */
	static protected function getDatabase(NanoApp $app) {
		return $app->getDatabase();
	}
}