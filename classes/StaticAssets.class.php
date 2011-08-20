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
	private $localRoot;

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

		$this->localRoot = $this->app->getDirectory();
	}

	/**
	 * Get full local path from request's path to given asset
	 */
	public function getLocalPath($requestPath) {
		// remove cache buster part
		if (strpos($requestPath, '/r') === 0) {
			$requestPath = preg_replace('#^/r\d+#', '', $requestPath);
		}

		$path = $this->localRoot . $requestPath;
		return $path;
	}

	/**
	 * Get full URL to given asset (include cache buster value)
	 */
	public function getUrlForAsset($asset) {

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

		// get local path to the asset
		$localPath = $this->getLocalPath($request->getPath());

		// does file exist?
		if (!file_exists($localPath)) {
			$response->setResponseCode(Response::NOT_FOUND);
			return false;
		}

		// get file's content
		$content = file_get_contents($localPath);

		// TODO: process file content

		// set headers and response's content
		$response->setResponseCode(Response::OK);
		$response->setContentType($this->types[$ext]);
		$response->setContent($content);

		// caching
		$response->setCacheDuration(30 * 86400 /* a month */);

		return true;
	}
}