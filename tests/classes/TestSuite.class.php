<?php

/**
 * Return test suites for core and modules
 *
 * $Id$
 */

class TestSuite extends PHPUnit_Framework_TestSuite {

	/**
	 * Return new instance of test suite class
	 */
	static public function init() {
		$suite = new self;
		$suite->setName('nanoPortal test suite');

		return $suite;
	}

	/**
	 * Return list of *Test.php files from given directory
	 */
	static private function scanDirectory($dir) {
		return glob(realpath($dir) . '/*Test.php');
	}

	/**
	 * Add test suite containing set of core tests
	 */
	public function addCoreTestSuite() {
		$suite = new parent;
		$suite->setName('nanoPortal core test suite');

		$dir = Nano::getCoreDirectory() . '/tests';
		$files = self::scanDirectory($dir);

		foreach($files as $file) {
			$suite->addTestFile($file);
		}

		$this->addTestSuite($suite);

		return $suite;
	}

	/**
	 * Run suite and print results to console
	 */
	public function run(PHPUnit_Framework_TestResult $result = NULL, $filter = FALSE, array $groups = array(), array $excludeGroups = array(), $processIsolation = FALSE) {
		// create results and printer objects
		$results = new PHPUnit_Framework_TestResult();
		$printer = new PHPUnit_TextUI_ResultPrinter(null /* $out */, true /* $verbose */, false /* $colors */, false /* $debug */);

		// "bind" printer to the results object
		$results->addListener($printer);

		// run test suite
		parent::run($results);

		// print results
		$printer->printResult($results);
	}
}