<?php

/**
 * nanoPortal base class
 *
 * $Id$
 */

class Nano {

	const VERSION = '0.01';

	private $dir = '';
	private $libraryDir = '';

	/**
	 * Framework's constructor
	 */
	private function __construct($dir) {
		// setup paths
		$this->dir = realpath($dir);
		$this->libraryDir = realpath($dir) . '/lib';
	}

	/**
	 * Initialize framework
	 */
	static public function init() {
		// load autoloader class
		require_once 'Autoloader.class.php';

		// setup autoloader
		Autoloader::init();
		Autoloader::scanDirectory(dirname(__FILE__));

		// set framework's directory
		$dir = dirname(__FILE__) . '/..';

		$nano = new self($dir);
		return $nano;
	}

	/**
	 * Return path to nanoPortal core
	 */
	public function getCoreDirectory() {
		return $this->dir;
	}

	/**
	 * Return path to nanoPortal libraries
	 */
	public function getLibDirectory() {
		return $this->libraryDir;
	}

	/**
	 * Add given library to include_path
	 */
	public function addLibrary($path) {
		// normalize path
		$fullPath = $this->getLibDirectory() . '/' . $path;

		// update include_path
		set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
	}
}