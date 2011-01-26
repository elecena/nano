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
	private $info;

	// HTTP request type (either GET or POST)
	private $type;

	/**
	 * Setup request object
	 */
	public function __construct(array $request, array $server) {
		$this->params = $request;
		$this->info = $server;

		// detect request type
		switch($server['REQUEST_METHOD']) {
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
		$ip = false;

		if (!empty($this->info['HTTP_CLIENT_IP'])) {
			$ip = $this->info['HTTP_CLIENT_IP'];
		}

		if (!empty($this->info['REMOTE_ADDR'])) {
			$ip = $this->info['REMOTE_ADDR'];
		}

		// proxy
		if (!empty($this->info['HTTP_X_FORWARDED_FOR'])) {
			$ipList = explode (', ', $this->info['HTTP_X_FORWARDED_FOR']);

			if ($ip) {
				$ipList[] = $ip;
				$ip = false;
			}

			// scan IP addresses for local ones (ignore them)
			foreach ($ipList as $v) {
				if (!preg_match('#^(192\.168|172\.16|10|224|240|127|0)\.#', $v)) {
					return $v;
				}
			}
		}

		return $ip ? $ip : null;
	}
}