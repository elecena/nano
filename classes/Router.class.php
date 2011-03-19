<?php

/**
 * Requests router
 *
 * $Id$
 */

class Router {

	const SEPARATOR = '/';

	private $app;

	// methods prefix (used by API)
	private $prefix;

	// URL mapping
	private $map = array();
	private $wildcardMap = array();

	// last routed request info
	private $lastRoute = null;

	function __construct(NanoApp $app, $prefix = '') {
		$this->app = $app;
		$this->prefix = $prefix;
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
			// empty path: /
			case 0:
				// use default module
				$moduleName = '';
				break;

			// module name only: /product
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

		// apply methods prefix - foo -> prefixFoo
		if ($this->prefix != '') {
			$methodName = $this->prefix . ucfirst($methodName);
			$defaultMethodName = $this->prefix . ucfirst($defaultMethodName);
		}

		#var_dump(array($moduleName, $methodName, $methodParams));

		// default value - means 404
		$ret = $this->lastRoute = null;

		// call selected module and method (with parameters)
		$module = $this->app->getModule($moduleName);

		if (!empty($module)) {
			// call selected method, otherwise call route method
			if (!is_callable(array($module, $methodName))) {
				$methodName = $defaultMethodName;
			}

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
}