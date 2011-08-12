<?php

/**
 * nanoPortal application  entry point for HTTP requests
 *
 * $Id$
 */

include 'app.php';

$request = $app->getRequest();

var_dump(__FILE__); var_dump($request); die(); // debug