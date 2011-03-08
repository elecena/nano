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

	// config
	private $config;

	// application's working directory
	private $dir;

	/**
	 * Create application based on given config
	 */
	function __construct($dir, $configSet = 'default') {
		$this->dir = realpath($dir);

		// read configuration
		$this->config = new Config($this->dir . '/config');
		$this->config->load($configSet);

		// setup cache
		$cacheType = $this->config->get('cache.driver');
		$cacheOptions = $this->config->get('cache');

		//$this->cache = Cache::factory($cacheType. $cacheOptions);
		
		// TODO: set request and connection to database
	}

	/**
	 * Return path to application
	 */
	public function getDirectory() {
		return $this->dir;
	}
	
	/**
	 * Return config
	 */
	public function getConfig() {
		return $this->config;
	}
}