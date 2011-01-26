<?php

/**
 * CLI script for running nanoPortal tests
 *
 * $Id$
 */

require_once '../nano.php';

// initialize instance of framework object
$nano = Nano::init();

// scan for helper classes for unit tests
Autoloader::scanDirectory(dirname(__FILE__) . '/classes');

// add PHPunit and libraries from PEAR to include_path
$nano->addLibrary('phpunit');
$nano->addLibrary('pear');
$nano->addLibrary('pear/PHP');

// load PHPunit
require_once 'PHPUnit/Autoload.php';

// construct global wrapper for test suites
$suite = TestSuite::init($nano);

// add "core" tests from /tests directory
$suite->addCoreTestSuite();

// run test suite
$suite->run();