<?php

/**
 * Wrapper for cURL based HTTP client
 *
 * @see http://php.net/manual/en/ref.curl.php
 *
 * $Id$
 */

class HttpClient {

	// User-Agent wysy³any przy ¿¹daniach
	private $userAgent;

	// uchwyt do cURLa
	private $handle;

	// wersja cURL'a
	private $version;

	// pobrane nag³ówki
	private $headers = array();

	// timeout
	private $timeout = 15;

	/**
	 * Setup HTTP client
	 */
	function __construct() {
		// info o cURLu
		$info = curl_version();
		$this->version = $info['version'];

		// ustaw nazwê przegl¹darki
		$this->setUserAgent('NanoPortal/' . Nano::VERSION . " libcurl/{$this->version}");

		// inicjalizuj i konfiguruj cURL'a
		$this->handle = curl_init();

		curl_setopt_array($this->handle, array(
			//CURLOPT_COOKIEFILE => "{$this->dir}/.cookies",
			//CURLOPT_COOKIEJAR => "{$this->dir}/.cookies",
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_HEADERFUNCTION => array(&$this, 'parseResponseHeader'),
			CURLOPT_MAXREDIRS => 2,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_USERAGENT => $this->userAgent,
		));
	}

	/**
	 * Set user agent identification used by HTTP client
	 */
	public function setUserAgent($userAgent) {
		$this->userAgent = $userAgent;
	}

	/**
	 * Get user agent identification used by HTTP client
	 */
	public function getUserAgent() {
		return $this->userAgent;
	}

	/**
	 * Send GET HTTP request for a given URL
	 */
	public function get($url, Array $query = array()) {
		// add request params
		if (!empty($query) && is_array($query)) {
			$url .= '?' . http_build_query($query);
		}

		// GET request
		curl_setopt($this->handle, CURLOPT_POST, false);

		return $this->sendRequest($url);
	}

	/**
	 * Send POST HTTP request for a given URL
	 */
	public function post($url, Array $fields = array()) {
		// dodaj parametry zapytania
		if (!empty($fields)) {
			curl_setopt($this->handle, CURLOPT_POSTFIELDS, http_build_query($fields));
		}

		// POST request
		curl_setopt($this->handle, CURLOPT_POST, true);

		return $this->sendRequest($url);
	}

	/**
	 * Send HTTP request
	 */
	private function sendRequest($url) {
		curl_setopt($this->handle, CURLOPT_URL, $url);

		// cleanup
		$this->headers = array();

		// send request and grab response
		ob_start();
		$res = curl_exec($this->handle);
		$ret = ob_get_clean();

		// pobierz informacje o zakoñczonym ¿¹daniu
		if ($res === true) {
			$info = curl_getinfo($this->handle); //var_dump($info);

			// redirect
			if ($url != $info['url']) {
			}

			// HTTP error?
			if ($info['http_code'] != 200) {
			}
		}
		else {
			// return an error
			$ret = false;
		}

		// TODO: return object of class HttpResponse
		return $ret;
	}

	/**
	 * Parse response's header
	 *
	 *@see http://it.toolbox.com/wiki/index.php/Use_curl_from_PHP_-_processing_response_headers
	 */
	function parseResponseHeader($ch, $raw) {
		// parse response's line
		$parts = explode(': ', trim($raw), 2);

		if (count($parts) == 2) {
			$this->headers[$parts[0]] = $parts[1];
		}

		return strlen($raw);
	}
}