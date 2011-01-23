<?php

/**
 * CLI script for running nanoPortal tests
 *
 * $Id$
 */

require_once '../nano.php';

// add PHPunit and libraries from PEAR to include_path
Nano::addLibrary('phpunit');
Nano::addLibrary('pear');
Nano::addLibrary('pear/PHP');

//echo get_include_path();


require_once 'PHP/CodeCoverage/Filter.php';
PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

if (extension_loaded('xdebug')) {
	xdebug_disable();
}

if (strpos('@php_bin@', '@php_bin') === 0) {
	set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
}

require_once 'PHPUnit/Autoload.php';

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

PHPUnit_TextUI_Command::main();