<?php

/**
 * PHP classes autoloading
 */

class Autoloader {

	// stores [class name] => [path to source file] pairs
	static private $classes = array();

	/**
	 * autoload given namespaces
	 * \Nano\Tests\TestResult => /path/to/nano/Tests/TestResult.class.php
	 *
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
	 */
	static private $namespaces = array();

	/**
	 * Setup autoloading feature
	 */
	static public function init() {
		// add composer autoloader
		require(__DIR__ . '/../vendor/autoload.php');

		spl_autoload_register('Autoloader::load');
	}

	/**
	 * Register class source file
	 */
	static public function add($class, $src) {
		self::$classes[$class] = $src;
	}

	/**
	 * Register namespace autoloader
	 */
	static public function addNamespace($ns, $path) {
		self::$namespaces[$ns] = $path;
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
	 *
	 * @param string $className class name
	 */
	static public function load($className) {
		// autoload classes from Nano namespace
		if (strpos($className, '\\') !== false) {
			// @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md#example-implementation
			$className = ltrim($className, '\\');

			// Nano\Tests\TestResult
			$parts = explode('\\', $className);
			$namespace = reset($parts); // // Nano
			$class = end($parts); // TestResult

			#var_dump($className); var_dump(array_slice($parts, 1, -1));

			if (!isset(self::$namespaces[$namespace])) {
				return;
			}

			$path = implode( DIRECTORY_SEPARATOR, array_slice($parts, 1, -1) );
			$class = str_replace('_', DIRECTORY_SEPARATOR, $class);

			$fullPath = sprintf(
				"%s/%s/%s.class.php",
				self::$namespaces[$namespace],
				strtolower($path),
				$class
			);

			#var_dump($fullPath);

			require_once $fullPath;
		}

		if (isset(self::$classes[$className])) {
			require_once self::$classes[$className];
		}
	}

	/**
	 * Factory helper
	 *
	 * Creates an instance of given class based on common prefix, class name and path to source files.
	 * Additional params can be provided for class constructor.
	 *
	 * @deprecated use namespaces instead
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
