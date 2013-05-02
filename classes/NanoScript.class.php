<?php

/**
 * General interface for CLI scripts
 */

abstract class NanoScript extends NanoObject {

	const LOGFILE = 'debug';

	function __construct(NanoApp $app) {
		parent::__construct($app);
		$this->init();
	}

	/**
	 * Setup the script
	 */
	protected function init() {}

	/**
	 * Script body
	 */
	abstract public function run();
}