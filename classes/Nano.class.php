<?php

/**
 * nanoPortal base class
 *
 * $Id$
 */

class Nano {

	const VERSION = '0.01';

	static private $dir = '';
	static private $libraryDir = '';

	/**
	 * Initialize framework (initialize classes autoloader, set directories)
	 */
	static public function init() {
		// load autoloader class
		require_once 'Autoloader.class.php';

		// setup autoloader
		Autoloader::init();
		Autoloader::scanDirectory(dirname(__FILE__));

		// set framework's directory
		$dir = dirname(__FILE__) . '/..';
		
		// setup paths
		self::$dir = realpath($dir);
		self::$libraryDir = realpath($dir) . '/lib';
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
	static public function addLibrary($path) {
		// normalize path
		$fullPath = self::getLibDirectory() . '/' . $path;

		// update include_path
		set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
	}
}