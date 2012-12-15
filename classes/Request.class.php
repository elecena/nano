<?php

/**
 * Wrapper for HTTP request
 *
 * $Id$
 */

class Request {

	// constants for request type
	const GET = 1;
	const POST = 2;
	const API = 3;
	const INTERNAL = 4;
	const CLI = 5;

	// stores a path fragment of the request (/foo/bar?q=123 -> /foo/bar)
	private $path;

	// stores request parameters as [param name] => [value] keys
	private $params;

	// stores request's info as [param name] => [value] keys
	private $env;

	// HTTP request type (either GET or POST)
	private $type;

	// cache client IP
	private $ip = null;

	/**
	 * Setup request object
	 */
	public function __construct(array $params = array(), array $env = array()) {
		$this->params = $params;
		$this->env = $env;

		// detect request type
		$method = isset($env['REQUEST_METHOD']) ? $env['REQUEST_METHOD'] : '';

		switch($method) {
			case 'POST':
				$this->type = self::POST;
				break;

			// "fake" request types for API dispatcher
			case 'API':
				$this->type = self::API;
				break;

			// "fake" request types for internal dispatcher
			case 'INTERNAL':
				$this->type = self::INTERNAL;
				break;

			// "fake" request types for CLI scripts
			case 'CLI':
				$this->type = self::CLI;
				break;

			case 'GET':
			default:
				$this->type = self::GET;
				break;
		}

		// set path from request's URI
		if (isset($env['REQUEST_URI'])) {
			// normalize REQUEST_URI - remove subdirectory from URL
			// host.net/foo/bar/site/module/param -> /module/param
			if (isset($env['SCRIPT_NAME'])) {
				$uri = $this->normalizeURI($env['SCRIPT_NAME'], $env['REQUEST_URI']);
			}
			else {
				$uri = $env['REQUEST_URI'];
			}

			$path = $this->getPathFromURI($uri);
			$this->setPath($path);
		}
	}

	/**
	 * Use provided script name to normalize request's URI
	 *
	 * Example: "http://host.net/foo/bar/site/module/param" should be routed as "/module/param"
	 */
	private static function normalizeURI($scriptName, $uri) {
		$subdirectory = dirname($scriptName);

		// ok, so we're in subdirectory
		if (strlen($subdirectory) > 1) {
			$uri = substr($uri, strlen($subdirectory));
		}

		return $uri;
	}

	/**
	 * Gets path part from request's URI
	 */
	private static function getPathFromURI($uri) {
		return urldecode(parse_url($uri, PHP_URL_PATH));
	}

	/**
	 * Gets values of params from request's URI
	 */
	private static function getParamsFromURI($uri) {
		$ret = array();
		$query = parse_url($uri, PHP_URL_QUERY);

		if (!is_null($query)) {
			parse_str($query, $ret);
		}

		return $ret;
	}

	/**
	 * Creates new instance of Request class with given params
	 */
	public static function newFromArray(array $params, $type = self::GET) {
		$request = new self($params);
		$request->type = $type;

		return $request;
	}

	/**
	 * Creates new instance of Request class from REQUEST_URI variable
	 */
	public static function newFromRequestURI($uri, $type = self::GET) {
		$path = self::getPathFromURI($uri);
		$params = self::getParamsFromURI($uri);

		return self::newFromPath($path, $params, $type);
	}

	/**
	 * Creates new instance of Request class from given path and params
	 */
	public static function newFromPath($path, Array $params = array(), $type = self::GET) {
		$request = new self($params);
		$request->type = $type;
		$request->setPath($path);

		return $request;
	}

	/**
	 * Creates new instance of Request class from given controller and method name (and optional parameters)
	 */
	public static function newFromControllerName($controllerName, $methodName = '', Array $params = array(), $type = self::GET) {
		if (is_array($methodName)) {
			$methodName = implode(Router::SEPARATOR, $methodName);
		}

		$path = Router::SEPARATOR . $controllerName . Router::SEPARATOR . $methodName;

		return self::newFromPath($path, $params, $type);
	}

	/**
	 * Set request type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Get value of given request parameter
	 *
	 * Return $default if parameter is not found
	 */
	public function get($param, $default = null) {
		return isset($this->params[$param]) ? $this->params[$param] : $default;
	}

	/**
	 * Get numeric value of given request parameter
	 *
	 * Return $default if parameter is not found
	 */
	public function getInt($param, $default = 0) {
		return intval($this->get($param, $default));
	}

	/**
	 * Get numeric value of given request parameter (with value limits applied)
	 *
	 * Return $default if parameter is not found (limits are not applied to default value)
	 */
	public function getIntLimit($param, $limitMin, $limitMax, $default = null) {
		$val = $this->get($param, null);

		// value not found - return $default
		if (is_null($val) && !is_null($default)) {
			return intval($default);
		}

		// value found - apply limits
		$val = intval($val);
		$val = max($val, $limitMin);
		$val = min($val, $limitMax);

		return $val;
	}

	/**
	 * Return if given checkbox is selected
	 */
	public function getChecked($param) {
		return $this->get($param) == 'on';
	}

	/**
	 * Return if current request was sent using POST method
	 */
	public function wasPosted() {
		return $this->type == self::POST;
	}

	/**
	 * Return if current request was sent using external API
	 */
	public function isApi() {
		return $this->type == self::API;
	}

	/**
	 * Return if current request was sent using internal API
	 */
	public function isInternal() {
		return $this->type == self::INTERNAL;
	}

	/**
	 * Return if current request was sent using CLI (command line interface)
	 */
	public function isCLI() {
		return $this->type == self::CLI;
	}

	/**
	 * Set URI of request
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * Get URI of request
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Get extension part of the URL
	 */
	public function getExtension() {
		$path = $this->getPath();
		$idx = strrpos($path, '.');

		// no extension found
		if ($idx === false) {
			return null;
		}

		$ext = substr($path, $idx + 1);
		return $ext;
	}

	/**
	 * Get IP address of client
	 *
	 * Return null if IP is malformed or not provided (CLI?)
	 */
	public function getIP() {
		if (!empty($this->ip)) {
			return $this->ip;
		}

		$ip = null;

		// @see http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
		$fields = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR'
		);

		// scan HTTP headers to find IP
		foreach($fields as $field) {
			if (!empty($this->env[$field])) {
				$tmp = $this->env[$field];

				if (!self::isLocalIP($tmp)) {
					$ip = $tmp;
					break;
				}
			}
		}

		return !is_null($ip) ? ($this->ip = $ip) : null;
	}

	/**
	 * Return whether given IP is a local IP
	 */
	static public function isLocalIP($ip) {
		return preg_match('#^(192\.168|172\.16|10|224|240|127|0)\.#', $ip) > 0;
	}
}