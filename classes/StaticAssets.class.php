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

	const PACKAGE_URL_PREFIX = '/package/';

	private $app;
	private $debug;
	private $router;

	// application's root directory
	private $localRoot;

	// cache buster value
	private $cb;

	// path to Content Delivery Network (if used)
	private $cdnPath;

	// should cache buster value be prepended to an URL?
	// example: /r200/foo/bar.js [true]
	// example: /foo/bar.js?r=200 [false]
	private $prependCacheBuster;

	// registered packages
	private $packages;

	// list of supported extensions with their mime types
	private $types = array(
		'css' => 'text/css; charset=utf-8',
		'js' => 'application/javascript; charset=utf-8',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
	);

	/**
	 * Setup assets server
	 */
	public function __construct(NanoApp $app) {
		$this->app = $app;

		$this->debug = $this->app->getDebug();
		$this->router = $this->app->getRouter();
		$this->localRoot = $this->app->getDirectory();

		// read configuration
		$config = $this->app->getConfig();

		$this->cb = intval($config->get('assets.cb', 1));
		$this->cdnPath = $config->get('assets.cdnPath', false);
		$this->prependCacheBuster = $config->get('assets.prependCacheBuster', true) === true;
		$this->packages = $config->get('assets.packages', array(
			'css' => array(),
			'js' => array()
		));
	}

	/**
	 * Creates an instance of given static assets processor
	 */
	public function getProcessor($assetType) {
		// use the default one
		$driver = $assetType;

		$instance = Autoloader::factory('StaticAssets', $driver, dirname(__FILE__) . '/staticassets', array($this->app));
		return $instance;
	}

	/**
	 * Get current cache buster value (used to invalidate cached assets)
	 */
	public function getCacheBuster() {
		return $this->cb;
	}

	/**
	 * Get path to CDN host
	 */
	public function getCDNPath() {
		return $this->cdnPath;
	}

	/**
	 * Get type of given package
	 */
	public function getPackageType($packageName) {
		if (isset($this->packages['css'][$packageName])) {
			return 'css';
		}
		else if (isset($this->packages['js'][$packageName])) {
			return 'js';
		}
		else {
			return false;
		}
	}

	/**
	 * Get list of file from the given package
	 *
	 * TODO: support packages in packages
	 */
	private function getPackageItems($packageName) {
		$packageType = $this->getPackageType($packageName);

		if (!empty($this->packages[$packageType][$packageName])) {
			return $this->packages[$packageType][$packageName];
		}
		else {
			return false;
		}
	}

	/**
	 * Get package name from given path
	 */
	public function getPackageName($path) {
		if (strpos($path, self::PACKAGE_URL_PREFIX) === 0) {
			// remove package URL prefix
			$path = substr($path, strlen(self::PACKAGE_URL_PREFIX));

			// remove extension
			$idx = strrpos($path, '.');
			if ($idx > 0) {
				return substr($path, 0, $idx);
			}
		}

		return false;
	}

	/**
	 * Remove prepended cache buster part from request path
	 */
	private function preprocessRequestPath($path) {
		if (strpos($path, '/r') === 0) {
			$path = preg_replace('#^/r\d+#', '', $path);
		}

		return $path;
	}

	/**
	 * Get full local path from request's path to given asset
	 */
	public function getLocalPath($path) {
		return $this->localRoot . $this->preprocessRequestPath($path);
	}

	/**
	 * Get full URL to given asset (include cache buster value)
	 */
	public function getUrlForAsset($asset) {
		$cb = $this->getCacheBuster();

		if ($this->prependCacheBuster) {
			// /r200/foo/bar.js
			$path = 'r' . $cb . Router::SEPARATOR . trim($asset, Router::SEPARATOR);
			$params = array();
		}
		else {
			// /foo/bar.js?r=200
			$path = $asset;
			$params = array('r' => $cb);
		}

		$url = $this->router->formatUrl($path, $params);

		// perform a rewrite for CDN
		$cdnPath = $this->getCDNPath();

		if ($cdnPath !== false) {
			$url = str_replace($this->router->getPathPrefix(), $cdnPath . Router::SEPARATOR, $url);
		}

		return $url;
	}

	/**
	 * Get full URL to given assets package (include cache buster value)
	 */
	public function getUrlForPackage($package) {
		// detect package type
		$type = $this->getPackageType($package);

		if ($type === false) {
			return false;
		}

		return $this->getUrlForAsset(self::PACKAGE_URL_PREFIX . "{$package}.{$type}");
	}

	/**
	 * Serve given request for a static asset / package
	 *
	 * This is an entry point
	 */
	public function serve(Request $request) {
		$ext = $request->getExtension();
		$response = $this->app->getResponse();

		if (is_null($ext) || !isset($this->types[$ext])) {
			$response->setResponseCode(Response::NOT_IMPLEMENTED);
			$response->setContent('This file type is not supported!');
			return false;
		}

		// remove cache buster from request path
		$requestPath = $this->preprocessRequestPath($request->getPath());

		$this->debug->log("Serving static asset - {$requestPath}");

		// check for package URL
		$packageName = $this->getPackageName($requestPath);

		// serve package or a single file
		$this->debug->time('asset');

		if ($packageName !== false) {
			$content = $this->servePackage($packageName, $ext);
		}
		else {
			$content = $this->serveSingleAsset($requestPath, $ext);
		}

		// error occured
		if ($content === false) {
			$response->setResponseCode(Response::NOT_FOUND);
			return false;
		}

		// benchmark
		$time = $this->debug->timeEnd('asset');
		$this->debug->log("Request {$requestPath} processed in {$time} s");

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

	/**
	 * Serve single static asset
	 *
	 * Performs additional checks and returns minified version of an asset
	 */
	private function serveSingleAsset($requestPath, $ext) {
		// get local path to the asset
		$localPath = $this->getLocalPath($requestPath);

		// does file exist?
		if (!file_exists($localPath)) {
			return false;
		}

		// security check - only serve files from within the application
		if (!$this->app->isInAppDirectory($localPath)) {
			return false;
		}

		// process file content
		$files = array($localPath);

		switch($ext) {
			case 'css':
				$content = $this->getProcessor('css')->processFiles($files);
				break;

			case 'js':
				$content = $this->getProcessor('js')->processFiles($files);
				break;

			// return file's content (images)
			default:
				$content = file_get_contents($localPath);
		}

		return $content;
	}

	/**
	 * Serve package of static assets
	 */
	private function servePackage($packageName, $ext) {
		$packageType = $this->getPackageType($packageName);

		if ($packageType === false) {
			$this->debug->log("Package doesn't exist");
			return false;
		}

		if ($packageType !== $ext) {
			$this->debug->log("Package type doesn't match extension in the request");
			return false;
		}

		if (!in_array($packageType, array('css', 'js'))) {
			$this->debug->log("Package can only be JS or CSS package");
			return false;
		}

		$this->debug->log("Serving assets package - {$packageName}");

		// make local paths to package files
		$packageFiles = $this->getPackageItems($packageName);

		$files = array();
		foreach($packageFiles as $file) {
			$this->debug->log("> {$file}");

			$files[] = $this->getLocalPath($file);
		}

		// process the whole package
		$processor = $this->getProcessor($packageType);
		$content = $processor->processFiles($files);

		return ($content != '') ? $content : false;
	}
}

/**
 * Common class for Static assets processors
 */
abstract class StaticAssetsProcessor {
	private $app;

	public function __construct(NanoApp $app) {
		$this->app = $app;
	}

	abstract public function processFiles(Array $files);
}