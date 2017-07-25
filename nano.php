<?php

/**
 * nanoPortal framework's entry point
 */

// show all errors and notices
error_reporting(E_ALL);

// UTF-8 stuff
mb_internal_encoding('utf-8');

// compatibility code
if (!array_key_exists('REQUEST_TIME_FLOAT', $_SERVER)) {
	// As of PHP 5.4.0, REQUEST_TIME_FLOAT is available in the $_SERVER superglobal array.
	// It contains the timestamp of the start of the request with microsecond precision.
	$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

require_once __DIR__ . '/vendor/autoload.php';
