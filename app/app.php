<?php

/**
 * nanoPortal application entry point for all requests
 *
 * index.php, api.php and static.php use this file as a bootstrap. You can perform extra actions here.
 *
 * $Id$
 */

require_once 'nano.php';

// initialize instance of framework object
Nano::init();

$app = Nano::app(dirname(__FILE__));