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
	
	// module's name
	private $name;
	
	function __construct($name) {
		$this->name = $name;
	}
}