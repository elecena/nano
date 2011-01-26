<?php

/**
 * Return test suites for core and modules
 *
 * $Id$
 */

class TestSuite extends PHPUnit_Framework_TestSuite {

	// instance of framework
	private $nano;

	/**
	 * Return new instance of test suite class
	 */
	static public function init(Nano $nano) {
		$suite = new self;
		$suite->setName('nanoPortal test suite');

		$suite->nano = $nano;

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

		$dir = $this->nano->getCoreDirectory() . '/tests';
		$files = self::scanDirectory($dir);

		if (!empty($files)) {
			$suite->addTestFiles($files);
		}

		$this->addTestSuite($suite);

		return $suite;
	}

	/**
	 * Run suite and print results to console
	 */
	public function run(PHPUnit_Framework_TestResult $result = NULL, $filter = FALSE, array $groups = array(), array $excludeGroups = array(), $processIsolation = FALSE) {
		// create results and printer objects
		$results = new TestResult();
		$printer = new ResultPrinter();

		// "bind" printer to the results object
		$results->addListener($printer);

		// run test suite
		parent::run($results);

		// print results
		$printer->printResult($results);
	}
}