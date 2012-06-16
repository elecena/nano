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
	const PACKAGES_SEPARATOR = ',';

	const PACKAGE_JS = 'js';
	const PACKAGE_CSS = 'css';

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
		$this->packages = $config->get('assets.packages', array());
	}

	/**
	 * Creates an instance of given static assets processor
	 */
	public function getProcessor($assetType) {
		// use the default one
		$driver = $assetType;

		$instance = Autoloader::factory('StaticAssets', $driver, dirname(__FILE__) . '/staticassets', array($this->app, $this));
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
	 * Returns whether given package exists
	 */
	public function packageExists($packageName) {
		return isset($this->packages[$packageName]);
	}

	/**
	 * Get list of assets of given type from given packages
	 */
	private function getPackagesItems(Array $packagesNames, $type) {
		$assets = array();

		foreach($packagesNames as $package) {
			if (!$this->packageExists($package)) {
				return false;
			}

			if (isset($this->packages[$package][$type])) {
				$assets = array_merge($assets, (array) $this->packages[$package][$type]);
			}
		}

		return $assets;
	}

	/**
	 * Get list of external assets of given type from given packages
	 */
	private function getPackagesExternalItems(Array $packagesNames, $type) {
		$assets = array();

		foreach($packagesNames as $package) {
			if (!$this->packageExists($package)) {
				return false;
			}

			if (!empty($this->packages[$package]['ext'][$type])) {
				$assets = array_merge($assets, (array) $this->packages[$package]['ext'][$type]);
			}
		}

		return $assets;
	}

	/**
	 * Remove packages with no assets of a given type
	 */
	public function filterOutEmptyPackages(Array $packages, $type) {
		$ret = array();

		foreach($packages as $package) {
			if ($this->packageExists($package) && isset($this->packages[$package][$type])) {
				$ret[] = $package;
			}
		}

		return $ret;
	}

	/**
	 * Resolve given packages dependencies
	 *
	 * Returns ordered list of modules to be loaded to satisfy dependencies (including modules that defined these dependencies)
	 *
	 * Dependencies are returned before provided packages to maintain correct loading order
	 */
	public function resolveDependencies(Array $packages) {
		$ret = $packages;

		foreach($packages as $packageName) {
			// package not found - return an error
			if (!$this->packageExists($packageName)) {
				return false;
			}

			$packageData = $this->packages[$packageName];

			if (isset($packageData['deps'])) {
				foreach((array)$packageData['deps'] as $dep) {
					$deps = $this->resolveDependencies((array)$dep);

					// add dependencies to the returned value
					$ret = array_merge($deps, $ret);
				}
			}
		}

		// make array contains unique values and fix indexing
		$ret = array_values(array_unique($ret));

		return $ret;
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
		// check for external assets
		if (strpos($asset, 'http') === 0) {
			return $asset;
		}

		$cb = $this->getCacheBuster();

		$asset = ltrim($asset, '/\\');

		// fix windows path
		$asset = str_replace(DIRECTORY_SEPARATOR, '/', $asset);

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
	 * Get full URL to a given file
	 */
	public function getUrlForFile($file) {
		$appDir = $this->app->getDirectory();
		$filePath = str_replace($appDir, '/', $file);

		return $this->getUrlForAsset($filePath);
	}

	/**
	 * Get flist of full URLs to get all assets defined by given single package (include cache buster value)
	 */
	public function getUrlsForPackage($package, $type) {
		return $this->packageExists($package) ? $this->getUrlsForPackages(array($package), $type) : false;
	}

	/**
	 * Get list of full URLs to get all assets defined by given packages (include cache buster value)
	 */
	public function getUrlsForPackages(Array $packages, $type) {
		// resolve dependencies
		$packages = $this->resolveDependencies($packages);

		// remove packages with no assets of a given type
		$packages = $this->filterOutEmptyPackages($packages, $type);

		$ret = false;

		if (!empty($packages)) {
			$package = implode(self::PACKAGES_SEPARATOR, $packages);

			$ret = array();
			
			// external assets
			$ret = array_merge($ret, $this->getPackagesExternalItems($packages, $type));

			// merged package(s)
			$ret[] = $this->getUrlForAsset(self::PACKAGE_URL_PREFIX . "{$package}.{$type}");
		}

		return $ret;
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
	 * Serve package(s) of static assets
	 */
	private function servePackage($package, $ext) {
		if (!in_array($ext, array(self::PACKAGE_CSS, self::PACKAGE_JS))) {
			$this->debug->log("Package can only be JS or CSS package");
			return false;
		}

		// get assets of given type ($ext) to serve
		$packages = array_unique( explode(self::PACKAGES_SEPARATOR, $package) );
		$assets = $this->getPackagesItems($packages, $ext);

		// no assets to serve or given package doesn't exist
		if (empty($assets)) {
			$this->debug->log("Packages don't exist or are empty");
			return false;
		}

		$this->debug->log('Serving assets package(s) - ' . implode(', ', $packages));

		// make local paths to package files
		$packageFiles = $this->getPackagesItems($packages, $ext);

		$files = array();
		foreach($packageFiles as $file) {
			$this->debug->log("> {$file}");

			$files[] = $this->getLocalPath($file);
		}

		// process the whole package
		$processor = $this->getProcessor($ext);
		$content = $processor->processFiles($files);

		return ($content != '') ? $content : false;
	}
}

/**
 * Common class for Static assets processors
 */
abstract class StaticAssetsProcessor {
	protected $app;
	protected $staticAssets;

	public function __construct(NanoApp $app, StaticAssets $staticAssets) {
		$this->app = $app;
		$this->staticAssets = $staticAssets;
	}

	abstract public function processFiles(Array $files);
}