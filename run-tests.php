#!/usr/bin/env php
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

// construct global wrapper for test suites
$suite = \Nano\Tests\TestSuite::init(false /* $performCodeCoverage */);

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
	// first, init the app that is going to be tested
	// load bootstrap and config files
	require $testsDirectory . '/../app.php';

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
