<?php

/**
 * nanoPortal application  entry point for static assets requests
 *
 * This script handles serving of images and CSS/JS files (including minified packages).
 *
 * $Id$
 */

include 'app.php';

$request = $app->getRequest();

var_dump(__FILE__); var_dump($request); die(); // debug