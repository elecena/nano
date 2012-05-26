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

	/**
	 * Factory helper
	 *
	 * Creates an instance of given class based on common prefix, class name and path to source files.
	 * Additional params can be provided for class constructor.
	 */
	static public function factory($prefix, $name, $directory, Array $params = array()) {
		$className = $prefix . ucfirst(strtolower($name));
		$srcFile = $directory . '/' . $className . '.class.php';

		// add to autoloader
		if (file_exists($srcFile)) {
			self::add($className, $srcFile);
		}

		// try to create class instance
		if (!class_exists($className)) {
			return null;
		}

		// http://www.php.net/manual/en/function.call-user-func-array.php#74427
		// make a reflection object
		$reflectionObj = new ReflectionClass($className);

		// use Reflection to create a new instance, using the $args (since PHP 5.1.3)
		$instance = $reflectionObj->newInstanceArgs($params);
		return $instance;
	}
}