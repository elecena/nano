<?php

/**
 * CLI script for running nanoPortal tests
 *
 * Usage:
 *  php run-tests.php
 *    run Nano core tests
 *
 *  php run-tests.php /home/user/myapp/tests
 *    run myapp test suite
 */

ini_set('memory_limit', '350M');

require_once 'nano.php';

// initialize instance of framework object
Nano::init();

// scan for helper classes for unit tests
Autoloader::scanDirectory(dirname(__FILE__) . '/classes/tests');

// add PHPunit and libraries from PEAR to include_path
Nano::addLibrary('phpunit');
Nano::addLibrary('pear');
Nano::addLibrary('pear/PHP');

// load PHPunit
require_once 'PHPUnit/Autoload.php';

// construct global wrapper for test suites
$suite = TestSuite::init(false /* $performCodeCoverage */);

// run-tests.php can be run with tests directory name as a parameter
if (!empty($argv[1])) {
	$currentDirectory = getcwd();
	$testsDirectory = $argv[1];

	if (!realpath($testsDirectory)) {
		// get absolute path for test directory
		$testsDirectory = realpath($currentDirectory . $testsDirectory);
	}
}

if (!empty($testsDirectory)) {
	$suite->addTestSuiteDirectory($testsDirectory, 'Application test suite');
}
else {
	// add "core" tests from /tests directory
	$suite->addCoreTestSuite();

	// add tests for application from /tests/app directory
	$suite->addTestSuiteDirectory(dirname(__FILE__) . '/tests/app/tests', 'nanoPortal test app suite');
}

// run test suite
$suite->run();
