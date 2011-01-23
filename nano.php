<?php

/**
 * nanoPortal framework's entry point
 *
 * $Id$
 */

// load autoloader class
require_once dirname(__FILE__) . '/classes/Autoloader.class.php';

// intialize autoloader and register core classes
Autoloader::init();
Autoloader::scanDirectory(dirname(__FILE__) . '/classes');

// init framework
Nano::init(dirname(__FILE__));