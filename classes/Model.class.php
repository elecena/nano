<?php

/**
 * Abstract class for representing nanoPortal's application model
 *
 * $Id$
 */

abstract class Model {

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
		$this->database = $app->getDatabase();
		$this->debug = $app->getDebug();
		$this->events = $app->getEvents();
	}
}