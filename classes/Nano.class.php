<?php

/**
 * nanoPortal base class
 *
 * $Id$
 */

class Nano {

	const VERSION = '0.01';

	// core directory
	static private $dir = '';

	// core libraries directory
	static private $libraryDir = '';

	/**
	 * Initialize framework (initialize classes autoloader, set directories)
	 */
	static public function init() {
		// load autoloader class
		require_once 'Autoloader.class.php';

		// setup autoloader
		Autoloader::init();

		// add /classes and /classes/utils dictionaries
		Autoloader::scanDirectory(dirname(__FILE__));
		Autoloader::scanDirectory(dirname(__FILE__) . '/utils');

		// set framework's directory
		$dir = dirname(__FILE__) . '/..';

		// setup paths
		self::$dir = realpath($dir);
		self::$libraryDir = self::$dir . '/lib';
	}

	/**
	 * Creates new instance of Nano application based on given configuration
	 */
	static public function app($dir, $configSet = 'default') {
		// initialize framework
		Nano::init();

		// create new application
		$app = new NanoApp($dir, $configSet);

		return $app;
	}

	/**
	 * Creates new instance of Nano application for command line
	 */
	static public function cli($dir, $logFile = 'script', $configSet = 'default') {
		// initialize framework
		Nano::init();

		// create new application
		$app = new NanoCliApp($dir, $configSet);

		// set debug log
		$debug = $app->getDebug();

		$debug->enableLog();
		$debug->setLogFile($logFile);
		$debug->clearLogFile();

		$debug->log('Nano v' . self::VERSION . ' CLI app started');
		$debug->log('----');

		return $app;
	}

	/**
	 * Return path to nanoPortal core
	 */
	static public function getCoreDirectory() {
		return self::$dir;
	}

	/**
	 * Return path to nanoPortal libraries
	 */
	static public function getLibDirectory() {
		return self::$libraryDir;
	}

	/**
	 * Add given library to include_path
	 */
	static public function addLibrary($directory) {
		// normalize path
		$fullPath = self::getLibDirectory() . '/' . $directory;

		// update include_path
		set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
	}
}