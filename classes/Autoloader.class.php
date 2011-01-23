<?php

/**
 * PHP classes autoloading
 *
 * $Id$
 */

class Autoloader {

	// stores [class name] => [path to source file] pairs
	static private $classes;

	/**
	 * Setup autoloading feature
	 */
	static public function init() {
		self::$classes = array();

		spl_autoload_register('Autoloader::load');
	}

	/**
	 * Register class source file
	 */
	static public function add($class, $src) {
		self::$classes[$class] = $src;
	}

	/**
	 * Scan given directory for *.class.php files and add them to the list of source files
	 */
	static public function scanDirectory($dir) {
		$files = glob(realpath($dir) . '/*.class.php');

		foreach($files as $path) {
			// remove '.class.php' suffix to get class name
			$className = substr(basename($path), 0, -10);

			self::add($className, $path);
		}
	}

	/**
	 * Try loading given PHP class
	 */
	static public function load($class) {
		if (isset(self::$classes[$class])) {
			require_once self::$classes[$class];
		}
	}
}