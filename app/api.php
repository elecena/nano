<?php

/**
 * nanoPortal application entry point for API requests
 */

include 'app.php';

use Nano\Output;
use Nano\Request;
use Nano\Response;

$request = $app->getRequest();
$response = $app->getResponse();

// request comes from API dispatcher
$request->setType(Request::API);

// parse request path to get response format
$path = $request->getPath();
$format = $request->getExtension();

if (!is_null($format)) {
	// remove extension part from request path
	$path = reset(explode('.', $path, 2));
	$request->setPath($path);
}
else {
	// default format if not specified
	$format = 'json';
}

// support JSONP format
if ($format == 'json') {
	$callback = trim($request->get('callback', ''));

	if ($callback != '') {
		$format = 'jsonp';
	}
}

// dispatch the request
$data = $app->dispatchRequest($request);

if ($data !== false) {
	// format the response
	$output = Output::factory($format, $data);

	if (!empty($callback)) {
		/* @var Output\OutputJsonp $output */
		$output->setCallback($callback);
	}

	// generate the response
	$response->setResponseCode(Response::OK);
	$response->setContent($output);
}

echo $response->render();

$app->tearDown();

#var_dump(__FILE__); var_dump($format); var_dump($request); var_dump($data); var_dump($response); die(); // debug