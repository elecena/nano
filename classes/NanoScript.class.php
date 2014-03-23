<?php

use Nano\NanoObject;

/**
 * General interface for CLI scripts
 */
abstract class NanoScript extends NanoObject {

	const LOGFILE = 'debug';

	private $isDebug = false;

	/**
	 * @param NanoApp $app
	 */
	function __construct(NanoApp $app) {
		$this->isDebug = (bool) getenv('DEBUG');

		parent::__construct($app);
		if ($this->isInDebugMode()) {
			$this->debug->log();
			$this->debug->log('Running in debug mode');
			$this->debug->log();
		}

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

	/**
	 * Called when the script execution is completed
	 */
	public function onTearDown(NanoApp $app) {
		// nop
	}

	/**
	 * Returns true if script is run in debug mode
	 *
	 * $ DEBUG=1 php script.php
	 *
	 * @return bool is script run in debug mode?
	 */
	protected function isInDebugMode() {
		return $this->isDebug;
	}
}
