<?php

/**
 * CLI script for running nanoPortal tests
 *
 * $Id$
 */

require_once '../nano.php';

// scan for helper classes
Autoloader::scanDirectory(dirname(__FILE__) . '/classes');

// add PHPunit and libraries from PEAR to include_path
Nano::addLibrary('phpunit');
Nano::addLibrary('pear');
Nano::addLibrary('pear/PHP');

// load PHPunit
require_once 'PHPUnit/Autoload.php';

// construct global wrapper for test suites
$suite = new PHPUnit_Framework_TestSuite();
$suite->setName('nanoPortal test suite');

// load "core" tests from /tests directory
$coreTestsSuite = TestSuite::getCoreTestSuite();
$suite->addTestSuite($coreTestsSuite);

// create results and printer objects
$results = new PHPUnit_Framework_TestResult();
$printer = new PHPUnit_TextUI_ResultPrinter(null /* $out */, true /* $verbose */, false /* $colors */, true /* $debug */);

// "bind" printer to the results object
$results->addListener($printer);

// run test suite
$suite->run($results);

// print results
$printer->printResult($results);