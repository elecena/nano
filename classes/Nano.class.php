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
	 * Initialize framework
	 */
	static public function init($dir) {
		// setup paths
		self::$dir = realpath($dir);
		self::$libraryDir = realpath($dir) . '/lib';
	}

	/**
	 * Add given library to include_path
	 */
	static public function addLibrary($path) {
		// normalize path
		$fullPath = self::$libraryDir . '/' . $path;

		// update include_path
		set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
	}
}