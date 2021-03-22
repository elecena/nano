<?php

/**
 * nanoPortal application entry point for all requests
 *
 * index.php, api.php, static.php and command-line scripts use this file as a bootstrap.
 * You can perform extra actions here. For instance: load different config on your development environment.
 */

require_once __DIR__ . '../vendor/autoload.php';

// initialize instance of framework object
Nano::init();

$app = Nano::app(dirname(__FILE__));

// run bootstrap file
require $app->getDirectory() . '/config/bootstrap.php';