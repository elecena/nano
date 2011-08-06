<?php

/**
 * nanoPortal application  entry point for API requests
 *
 * $Id$
 */

include 'app.php';

$request = $app->getRequest();

// request comes from API dispatcher
$request->setType(Request::API);

var_dump(__FILE__); var_dump($request); die(); // debug