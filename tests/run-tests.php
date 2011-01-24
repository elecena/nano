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
$suite = TestSuite::init();

// add "core" tests from /tests directory
$suite->addCoreTestSuite();

// run test suite
$suite->run($results);