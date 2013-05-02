<?php

/**
 * Return test suites for core and modules
 */

class TestSuite extends PHPUnit_Framework_TestSuite {

	private $performCodeCoverage;

	/**
	 * Return new instance of test suite class
	 */
	static public function init($performCodeCoverage = false) {
		$suite = new self;
		$suite->setName('nanoPortal test suite');
		$suite->peformCodeCoverage = $performCodeCoverage;

		return $suite;
	}

	/**
	 * Return list of *Test.php files from given directory
	 */
	static private function scanDirectory($dir) {
		return glob(realpath($dir) . '/*Test.php');
	}

	/**
	 * Creates test suite with *Test.php files from given directory
	 */
	private function createTestSuiteForDirectory($dir, $name) {
		$suite = new parent;
		$suite->setName($name);

		$files = self::scanDirectory($dir);

		if (!empty($files)) {
			$suite->addTestFiles($files);
		}

		return $suite;
	}

	/**
	 * Add test suite containing set of core tests
	 */
	public function addCoreTestSuite() {
		$dir = Nano::getCoreDirectory() . '/tests';
		$suite = $this->createTestSuiteForDirectory($dir, 'nanoPortal core test suite');

		$this->addTestSuite($suite);

		return $suite;
	}

	/**
	 * Add test suite for given directory
	 */
	public function addTestSuiteDirectory($dir, $name) {
		$suite = $this->createTestSuiteForDirectory($dir, $name);

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

		// collect code coverage report
		if (!empty($this->performCodeCoverage)) {
			$results->collectCodeCoverageInformation(true);
		}

		// "bind" printer to the results object
		$results->addListener($printer);

		// run test suite
		parent::run($results);

		// print results
		$printer->printResult($results);

		// code coverage report
		$codeCoverage = $results->getCodeCoverageSummary();

		if (!empty($this->performCodeCoverage)) {
			echo "\nCode coverage report:\n";

			foreach($codeCoverage as $file => $info) {
				// blacklist files from libraries directory
				if (strncmp($file, DIRECTORY_SEPARATOR . 'lib', 4) == 0) {
					continue;
				}

				// ignore test classes
				if (strpos($file, 'classes'. DIRECTORY_SEPARATOR . 'tests') !== false) {
					continue;
				}

				// report *.class.php files only
				if (strpos($file, '.class.php') === false) {
					continue;
				}

				$file = basename($file);
				echo "* {$file} - {$info['coverage']}% covered\n";

				if ($info['notCoveredLines'] != '') {
					echo " -> lines not covered: {$info['notCoveredLines']}\n\n";
				}
			}
		}
	}
}