<?php

/**
 * Return test suites for core and modules
 *
 * $Id$
 */

class TestSuite {

	/**
	 * Scan given directory for *.class.php files and add them to the list of source files
	 */
	static private function scanDirectory($dir) {
		return glob(realpath($dir) . '/*Test.php');
	}

	/**
	 * Return test suite object containing set of core tests
	 */
	static public function getCoreTestSuite() {
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->setName('nanoPortal core test suite');

		$dir = Nano::getCoreDirectory() . '/tests';
		$files = self::scanDirectory($dir);

		if (!empty($files)) {
			$suite->addTestFiles($files);
		}

		return $suite;
	}
}