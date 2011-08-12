<?php

/**
 * nanoPortal application entry point for API requests
 *
 * $Id$
 */

include 'app.php';

$request = $app->getRequest();
$response = $app->getResponse();

// request comes from API dispatcher
$request->setType(Request::API);

// TODO: parse request path
$path = $request->getPath();
$format = 'json';

$parts = explode('.', $path, 2);

if (count($parts) == 2) {
	$request->setPath($parts[0]);
	$format = $parts[1];
}

// support JSONP format
if ($format == 'json') {
	$callback = $request->get('callback');

	if (!empty($callback)) {
		$format = 'jsonp';
	}
}

// dispatch the request
$data = $app->dispatchRequest($request);

$data = array('foo' => 'bar');

// format the response
$output = Output::factory($format, $data);

if (!empty($callback)) {
	$output->setCallback($callback);
}

// generate the response
$response->setResponseCode(Response::OK);
$response->setContent($output);

echo $response->render();

//var_dump(__FILE__); var_dump($request); var_dump($data); var_dump($response); die(); // debug