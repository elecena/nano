<?php

/**
 * Class for representing nanoPortal's application for command line interface
 *
 * $Id$
 */

class NanoCliApp extends NanoApp {

	/**
	 * Create application based on given config
	 */
	function __construct($dir, $configSet = 'default', $logFile = 'script') {
		parent::__construct($dir, $configSet, $logFile);

		// set request
		$this->request = new Request(array(), array('REQUEST_METHOD' => 'CLI'));

		// run bootstrap file - web application runs bootstrap from app.php
		$app = $this;
		require $this->getDirectory() . '/config/bootstrap.php';
	}
}