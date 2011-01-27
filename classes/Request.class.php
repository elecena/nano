<?php

/**
 * Wrapper for HTTP request
 *
 * $Id$
 */

class Request {

	const GET = 1;
	const POST = 2;

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
	public function __construct(array $request, array $env = array()) {
		$this->params = $request;
		$this->env = $env;

		// detect request type
		switch($env['REQUEST_METHOD']) {
			case 'POST':
				$this->type = self::POST;
				break;

			case 'GET':
			default:
				$this->type = self::GET;
				break;
		}
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