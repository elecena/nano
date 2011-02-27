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
	function __construct($dir, $config) {
		$this->dir = realpath($dir);
		$this->config = $config;

		// TODO: set cache, request and connection to database
	}

	/**
	 * Return path to application
	 */
	public function getDirectory() {
		return $this->dir;
	}
}