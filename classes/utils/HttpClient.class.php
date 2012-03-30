<?php

/**
 * Wrapper for cURL based HTTP client
 *
 * @see http://php.net/manual/en/ref.curl.php
 *
 * $Id$
 */

class HttpClient {

	// HTTP request types
	const GET = 1;
	const POST = 2;
	const HEAD = 3;

	// User-Agent wysy³any przy ¿¹daniach
	private $userAgent;

	// cURL resource
	private $handle;

	// cURL version
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

		// set up cURL library
		$this->handle = curl_init();

		// set user agent
		$this->setUserAgent('NanoPortal/' . Nano::VERSION . " libcurl/{$this->version}");

		curl_setopt_array($this->handle, array(
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_HEADERFUNCTION => array($this, 'parseResponseHeader'),
			CURLOPT_MAXREDIRS => 2,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => $this->timeout,
		));
	}

	/**
	 * Close a session,free all resources and store cookies in jar file
	 */
	public function close() {
		curl_close($this->handle);
	}

	/**
	 * Set proxy to be used for HTTP requests
	 */
	public function setProxy($proxy, $type = CURLPROXY_HTTP) {
		curl_setopt($this->handle, CURLOPT_PROXY, $proxy);
		curl_setopt($this->handle, CURLOPT_PROXYTYPE, $type);
	}

	/**
	 * Set user agent identification used by HTTP client
	 */
	public function setUserAgent($userAgent) {
		$this->userAgent = $userAgent;

		curl_setopt($this->handle, CURLOPT_USERAGENT, $this->userAgent);
	}
	
	/**
	 * Get user agent identification used by HTTP client
	 */
	public function getUserAgent() {
		return $this->userAgent;
	}

	/**
	 * Set timeout for a single request
	 */
	public function setTimeout($timeout) {
		$this->timeout = $timeout;

		curl_setopt($this->handle, CURLOPT_TIMEOUT, $this->timeout);
	}

	/**
	 * Use given cookie jar file
	 */
	public function useCookieJar($jarFile) {
		curl_setopt_array($this->handle, array(
			CURLOPT_COOKIEFILE => $jarFile,
			CURLOPT_COOKIEJAR => $jarFile,
		));
	}

	/**
	 * Send GET HTTP request for a given URL
	 */
	public function get($url, Array $query = array()) {
		// add request params
		if (!empty($query) && is_array($query)) {
			$url .= '?' . http_build_query($query);
		}

		return $this->sendRequest(self::GET, $url);
	}

	/**
	 * Send POST HTTP request for a given URL
	 */
	public function post($url, Array $fields = array()) {
		// add request POST fields
		if (!empty($fields)) {
			curl_setopt($this->handle, CURLOPT_POSTFIELDS, http_build_query($fields));
		}

		return $this->sendRequest(self::POST, $url);
	}

	/**
	 * Send HEAD HTTP request for a given URL
	 */
	public function head($url, Array $query = array()) {
		// add request params
		if (!empty($query)) {
			$url .= '?' . http_build_query($query);
		}

		return $this->sendRequest(self::HEAD, $url);
	}

	/**
	 * Send HTTP request
	 */
	private function sendRequest($type, $url) {
		// send requested type of HTTP request
		curl_setopt($this->handle, CURLOPT_POST, false);
		curl_setopt($this->handle, CURLOPT_NOBODY, false);

		switch ($type) {
			case self::POST:
				curl_setopt($this->handle, CURLOPT_POST, true);
				break;

			case self::HEAD:
				// @see http://curl.haxx.se/mail/curlphp-2008-03/0072.html
				curl_setopt($this->handle, CURLOPT_NOBODY, true);
				break;

			case self::GET:
			default:
				// nop
		}

		curl_setopt($this->handle, CURLOPT_URL, $url);

		// cleanup
		$this->headers = array();

		// send request and grab response
		ob_start();
		$res = curl_exec($this->handle);
		$content = ob_get_clean();

		// get response
		if ($res === true) {
			// @see http://pl2.php.net/curl_getinfo
			$info = curl_getinfo($this->handle); //var_dump($info);

			// return HTTP response object
			$response = new HttpResponse();

			// set response code
			$response->setResponseCode($info['http_code']);

			// set response headers
			$response->setHeaders($this->headers);

			// set response content
			$response->setContent($content);

			// set response location (useful for redirects)
			$response->setLocation($info['url']);
		}
		else {
			// return an error
			$response = false;

			// curl_error($this->handle)
		}

		return $response;
	}

	/**
	 * Parse response's header
	 *
	 * @see http://it.toolbox.com/wiki/index.php/Use_curl_from_PHP_-_processing_response_headers
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