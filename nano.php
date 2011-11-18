<?php

/**
 * nanoPortal framework's entry point
 *
 * $Id$
 */

// show all errors and notices
error_reporting(E_ALL);

// UTF-8 stuff
mb_internal_encoding('utf-8');

// load autoloader class
require_once dirname(__FILE__) . '/classes/Nano.class.php';