<?php

/**
 * nanoPortal application entry point for static assets requests
 *
 * This script handles serving of images and CSS/JS files (including minified packages).
 */

include 'app.php';

$request = $app->getRequest();
$response = $app->getResponse();

// initialize static assets handler
$assets = $app->factory('StaticAssets');

// handle the request
$assets->serve($request);

echo $response->render();

$app->tearDown();
