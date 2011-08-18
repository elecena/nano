<?php

/**
 * Static assets handling
 *
 * Includes sending proper caching HTTP headers, packages handling, minification of JS and CSS files
 * and embedding of images in CSS file (data-URI encoding)
 *
 * $Id$
 */

class StaticAssets {

	private $app;

	// registered packages
	private $packages = array(
		'css' => array(),
		'js' => array(),
	);

	// list of supported extensions with their mime types
	private $types = array(
		'css' => 'text/css; charset=utf-8',
		'js' => 'application/javascript; charset=utf-8',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpg' => 'image/jpg',
	);

	/**
	 * Setup assets server
	 */
	public function __construct(NanoApp $app) {
		$this->app = $app;
	}

	/**
	 * Serve given request for a static asset / package
	 */
	public function serve(Request $request) {
		$ext = $request->getExtension();
		$response = $this->app->getResponse();

		if (is_null($ext) || !isset($this->types[$ext])) {
			$response->setResponseCode(Response::NOT_IMPLEMENTED);
			$response->setContent('This file type is not supported!');
			return false;
		}

		// TODO: process file content

		// set headers
		$response->setResponseCode(Response::OK);
		$response->setContentType($this->types[$ext]);

		// caching
		$response->setCacheDuration(30 * 86400 /* a month */);

		return true;
	}
}