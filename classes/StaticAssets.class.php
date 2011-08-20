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
	 * Creates an instance of given static assets processor
	 */
	public static function factory($driver) {
		return Autoloader::factory('StaticAssets', $driver, dirname(__FILE__) . '/staticassets');
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
	 * Get full URL to given assets package (include cache buster value)
	 */
	public function getUrlForPackage($asset) {

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

		// process file content
		switch($ext) {
			case 'css':
				$content = self::factory('css')->process($localPath);
				break;

			case 'js':
				$content = self::factory('js')->process($localPath);
				break;

			// return file's content
			default:
				$content = file_get_contents($localPath);
		}

		// set headers and response's content
		$response->setResponseCode(Response::OK);
		$response->setContentType($this->types[$ext]);
		$response->setContent($content);

		// caching
		// @see @see http://developer.yahoo.com/performance/rules.html
		$response->setCacheDuration(30 * 86400 /* a month */);
		$response->setLastModified('1 January 2000');

		return true;
	}
}

/**
 * Common interface for Static assets processors
 */
interface IStaticAssetsProcessor {
	public function process($file);
}