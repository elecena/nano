<?php

/**
 * CLI script for running nanoPortal tests
 *
 * $Id$
 */

ini_set('memory_limit', '64M');
 
require_once '../nano.php';

// initialize instance of framework object
Nano::init();

// scan for helper classes for unit tests
Autoloader::scanDirectory(dirname(__FILE__) . '/classes');

// add PHPunit and libraries from PEAR to include_path
Nano::addLibrary('phpunit');
Nano::addLibrary('pear');
Nano::addLibrary('pear/PHP');

// load PHPunit
require_once 'PHPUnit/Autoload.php';

// construct global wrapper for test suites
$suite = TestSuite::init();

// add "core" tests from /tests directory
$suite->addCoreTestSuite();

// add tests for application from /tests/app directory
$suite->addTestSuiteDirectory(dirname(__FILE__) . '/app/tests', 'nanoPortal test app suite');

// run test suite
$suite->run();