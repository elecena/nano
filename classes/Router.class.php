<?php

/**
 * Requests router
 */

class Router {

	const SEPARATOR = '/';
	const DEFAULT_METHOD = 'index';

	private $app;
	private $debug;

	// URL mapping
	private $map = array();
	private $wildcardMap = array();

	// last routed request info
	private $lastRoute = null;

	// URL to application's home page (i.e. /)
	private $homeUrl;

	function __construct(NanoApp $app) {
		$this->app = $app;
		$this->debug = $this->app->getDebug();

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
	 *
	 * Returns either raw controller's data, data wrapped in Output object or false
	 */
	public function route(Request $request) {
		// get and normalize path
		$path = $request->getPath();
		$path = $this->normalize($path);

		// apply route mapping
		$path = $this->applyMap($path);

		$this->debug->log(__METHOD__ . " - routing '{$path}'");

		// split path by separators
		$pathParts = explode(self::SEPARATOR, $path);

		/*
		 * Parse path /product/show/123/456 to:
		 *  - controller name: product
		 *  - method: show
		 *  - parameters: 123, 456
		 */

		// default controller's method used for routing
		$methodName = $defaultMethodName = self::DEFAULT_METHOD;
		$methodParams = array();

		switch (count($pathParts)) {
			// controller name only: /product (or an empty path)
			case 1:
				$controllerName = $pathParts[0];
				$methodName = null;
				break;

			// controller and method name: /product/bar (with parameters)
			case 2:
			default:
				$controllerName = $pathParts[0];
				$methodName = strtolower($pathParts[1]);

				$methodParams = array_slice($pathParts, 2) + $methodParams;
		}

		// sanitize and normalize
		$controllerName = ucfirst(strtolower($controllerName));

		#var_dump(array($controllerName, $methodName, $methodParams));

		// default value - means 404
		$ret = false;
		$this->lastRoute = null;

		// call selected controller and method (with parameters)
		$controller = $this->app->getController($controllerName);

		if ($controller instanceof Controller) {
			// use indexAPI method to route API requests
			$defaultAPImethod = self::DEFAULT_METHOD . 'API';

			if ($request->isAPI() && is_callable(array($controller, $defaultAPImethod))) {
				$defaultMethodName = $defaultAPImethod;
			}

			// call selected method, otherwise call route method
			if (!is_callable(array($controller, $methodName))) {
				// if method doesn't exist, push it as a first parameter
				if (is_string($methodName)) {
					array_unshift($methodParams, $methodName);
				}

				$methodName = $defaultMethodName;
			}

			#var_dump(array($controllerName, $methodName, $methodParams));

			// fill array of parameters passed with null values
			$params = array_merge($methodParams, array_fill(0, 5, null));

			if (is_callable(array($controller, $methodName))) {
				// create a view
				$view = new View($this->app, $controller);
				$view->setMethod($methodName);
				$view->setTemplateName($methodName);

				$controller->setView($view);

				// use provided request and created view when executing controller's method
				$controller->setRequest($request);
				$controller->init();

				// call the controller's method and pass provided parameters
				$ret = call_user_func_array(array($controller, $methodName), $params);

				// store info about this route
				// TODO: store in view object
				$this->lastRoute = array(
					'controller' => strtolower($controllerName),
					'method' => $methodName,
					'params' => $methodParams,
				);

				if ($ret === false) {
					// this basically means that the request can't be routed (i.e. HTTP 404)
					$this->debug->log(__METHOD__ . " - controller '{$controllerName}' returned 404 status");
				}
				else if (is_string($ret)) {
					// pass through any string
					$this->debug->log(__METHOD__ . " - string passed");
				}
				else {
					// get controller's data
					$data = $controller->getData();
					$format = $controller->getFormat();

					if (!is_null($format)) {
						$this->debug->log(__METHOD__ . " - {$format} format forced");

						// use provided format to render the data
						$ret = Output::factory($format, $data);
					}
					else {
						// wrap data in a template
						$template = $view->getTemplate();
						$templateName = $view->getTemplateName();

						$ret = Output::factory('template', $data);
						$ret->setTemplate($template);
						$ret->setTemplateName($templateName);
					}

					$this->debug->log(__METHOD__ . " - {$controllerName}::{$methodName} done");
				}
			}
			else {
				$this->debug->log(__METHOD__ . " - {$controllerName}::{$methodName} is not callable!");
			}
		}
		else {
			$this->debug->log(__METHOD__ . " - routing '{$controllerName}' controller failed!");
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
	 * Get path to application's home page
	 */
	public function getPathPrefix() {
		// parse homepage's URL
		$pathPrefix = self::SEPARATOR . $this->normalize(parse_url($this->homeUrl, PHP_URL_PATH));

		if (strlen($pathPrefix) > 1) {
			$pathPrefix .= self::SEPARATOR;
		}

		return $pathPrefix;
	}

	/**
	 * Sanitize given string to be used in URL
	 *
	 * Replace all non alphanumeric characters with a dash
	 */
	public function sanitize($string) {
		$string = mb_strtolower(trim($string));
		$string = strtr($string, array(
			'ą' => 'a',
			'ć' => 'c',
			'ę' => 'e',
			'ł' => 'l',
			'ń' => 'n',
			'ó' => 'o',
			'ś' => 's',
			'ż' => 'z',
			'ź' => 'z'
		));

		$string = preg_replace('#[^a-z0-9]+#', '-', $string);
		$string = trim($string, '-');

		return $string;
	}

	/**
	 * Format a local link for a given route
	 */
	public function formatUrl(/* $controllerName, $methodName, ..., $params = array() */) {
		$args = func_get_args();

		// GET params can be provided as the last function argument
		$params = is_array(end($args)) ? array_pop($args) : array();

		// format request path
		$path = implode(self::SEPARATOR, $args);

		// build a link
		$link = $this->getPathPrefix() . $this->normalize($path);

		// add request parameters
		if (!empty($params)) {
			$link .= '?' . http_build_query($params, '', '&');
		}

		return $link;
	}

	/**
	 * Format a external link (i.e. with host name) for a given route
	 */
	public function formatFullUrl(/* $controllerName, $methodName, ..., $params = array() */) {
		// parse homepage's URL
		$scheme = parse_url($this->homeUrl, PHP_URL_SCHEME);
		$host = parse_url($this->homeUrl, PHP_URL_HOST);

		$args = func_get_args();

		return $scheme . '://' . $host . call_user_func_array(array($this, 'formatUrl'), $args);
	}
}