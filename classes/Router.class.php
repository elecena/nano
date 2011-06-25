<?php

/**
 * Requests router
 *
 * $Id$
 */

class Router {

	const SEPARATOR = '/';

	private $app;

	// URL mapping
	private $map = array();
	private $wildcardMap = array();

	// last routed request info
	private $lastRoute = null;

	// URL to application's home page
	private $homeUrl;

	function __construct(NanoApp $app) {
		$this->app = $app;

		$config = $this->app->getConfig();

		// set URL to app's home page
		$this->homeUrl = $config->get('home');

		// set alias to index page (/)
		$index = $config->get('index');

		if (!is_null($index)) {
			$this->map('/', $index);
		}
	}

	/**
	 * Normalize given URL
	 *
	 * Trim separator and extra chars given
	 */
	private function normalize($url, $extraChars = '') {
		$url = rtrim($url, self::SEPARATOR . $extraChars);
		$url = ltrim($url, self::SEPARATOR);

		return $url;
	}

	/**
	 * Add route mapping
	 *
	 * /foo/bar/* -> /bar
	 * /test -> /bar/test
	 */
	public function map($from, $to) {
		$isWildcard = substr($from, -2) == self::SEPARATOR . '*';

		$from = $this->normalize($from, '*');
		$to = $this->normalize($to, '*');

		if ($isWildcard) {
			$this->wildcardMap[$from . self::SEPARATOR] = $to;
		}
		else {
			$this->map[$from] = $to;
		}
	}

	/**
	 * Return first matching mapping for given URL
	 */
	private function applyMap($path) {
		$url = $path;

		// match "straight" mapping
		if (isset($this->map[$path])) {
			$url = $this->map[$path];
		}
		// now try with wildcard mapping
		else {
			foreach($this->wildcardMap as $from => $to) {
				// for /show/* entry match /show/123, but do not match /show
				if (substr($url, 0, strlen($from)) == $from) {
					// pass parameters "hidden" under *
					$url = $to . self::SEPARATOR . substr($url, strlen($from));
				}
			}
		}

		return $url;
	}

	/**
	 * Route given request
	 */
	public function route(Request $request) {
		// get and normalize path
		$path = $request->getPath();
		$path = $this->normalize($path);

		// apply route mapping
		$path = $this->applyMap($path);

		// split path by separators
		$pathParts = explode(self::SEPARATOR, $path);

		/*
		 * Parse path /product/show/123/456 to:
		 *  - module name: product
		 *  - method: show
		 *  - parameters: 123, 456
		 */

		// default module's method used for routing
		$methodName = $defaultMethodName = 'route';
		$methodParams = array();

		switch (count($pathParts)) {
			// module name only: /product (or an empty path)
			case 1:
				$moduleName = $pathParts[0];
				break;

			// module and method name: /product/bar (with parameters)
			case 2:
			default:
				$moduleName = $pathParts[0];
				$methodName = $pathParts[1];

				$methodParams = array_slice($pathParts, 2) + $methodParams;
		}

		// sanitize and normalize
		$moduleName = ucfirst(strtolower($moduleName));
		$methodName = strtolower($methodName);

		#var_dump(array($moduleName, $methodName, $methodParams));

		// default value - means 404
		$ret = $this->lastRoute = null;

		// call selected module and method (with parameters)
		$module = $this->app->getModule($moduleName);

		if (!empty($module)) {
			// call selected method, otherwise call route method
			if (!is_callable(array($module, $methodName))) {
				// if method doesn't exist, push it as a first parameter
				array_unshift($methodParams, $methodName);

				$methodName = $defaultMethodName;
			}

			#var_dump(array($moduleName, $methodName, $methodParams));

			// fill array of parameters passed with null values
			$params = array_merge($methodParams, array_fill(0, 5, null));

			if (is_callable(array($module, $methodName))) {
				// use provided request when executing module's method
				$module->setRequest($request);

				$ret = call_user_func_array(array($module, $methodName), $params);

				// store info about this route
				$this->lastRoute = array(
					'module' => strtolower($moduleName),
					'method' => $methodName,
					'params' => $methodParams,
				);
			}
		}

		return $ret;
	}

	/**
	 * Get info about last route
	 */
	public function getLastRoute() {
		return $this->lastRoute;
	}

	/**
	 * Format a local link for a given route
	 */
	public function link($path, $params = array()) {
		// parse homepage's URL
		$pathPrefix = self::SEPARATOR . $this->normalize(parse_url($this->homeUrl, PHP_URL_PATH));

		if (strlen($pathPrefix) > 1) {
			$pathPrefix .= self::SEPARATOR;
		}

		// build a link
		$link = $pathPrefix . $this->normalize($path);

		// add request parameters
		if (!empty($params)) {
			$link .= '?' . http_build_query($params, '', '&');
		}

		return $link;
	}

	/**
	 * Format a external link (i.e. with host name) for a given route
	 */
	public function externalLink($path, $params = array()) {
		// parse homepage's URL
		$scheme = parse_url($this->homeUrl, PHP_URL_SCHEME);
		$host = parse_url($this->homeUrl, PHP_URL_HOST);

		return $scheme . '://' . $host . $this->link($path, $params);
	}
}