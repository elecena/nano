<?php

/**
 * Handles response (sets HTTP headers, wraps output content)
 *
 * $Id$
 */

class Response {

	// date format to be used for HTTP headers
	const DATE_RFC1123 = 'D, d M Y H:i:s \G\M\T';

	// popular HTTP codes
	// @see http://www.ietf.org/rfc/rfc2616.txt

	// Successful 2xx
	const OK = 200;
	const NO_CONTENT = 204;
	// Redirection 3xx
	const MOVED_PERMANENTLY = 301;
	const FOUND = 302;
	const NOT_MODIFIED = 304;
	// Client Error 4xx
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	// Server Error 5xx
	const SERVICE_UNAVAILABLE = 503;

	// HTML to be returned to the client
	private $content;

	// key / value list of HTTP headers to be emitted
	private $headers = array();

	// response HTTP code (defaults to 404 - Not Found)
	private $responseCode = 404;

	// used for generating X-Response-Time header
	private $responseStart;

	// $_SERVER global
	private $env;

	/**
	 * Set the timestamp of the response start
	 */
	public function __construct($env = array()) {
		$this->responseStart = microtime(true);
		$this->env = $env;
	}

	/**
	 * Set output's content
	 */
	public function setContent($content) {
		// handle output's wrappers
		if ($content instanceof Output) {
			// render data
			$this->content = $content->render();

			// use proper content type
			$this->setHeader('Content-type', $content->getContentType());
		}
		// handle strings
		else if (is_string($content)) {
			$this->content = $content;
		}
	}

	/**
	 * Get output's content
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Set the value of given header
	 */
	public function setHeader($name, $value) {
		$this->headers[$name] = $value;
	}

	/**
	 * Get the value of given header
	 */
	public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}

	/**
	 * Get all headers
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * (Try to) emit HTTP headers to the browser. Returns false in case headers were already sent.
	 */
	private function sendHeaders() {
		// set X-Response-Time header
		$this->setHeader('X-Response-Time', $this->getResponseTime());

		// don't emit, if already emitted :)
		if (headers_sent()) {
			return false;
		}

		// emit HTTP protocol and response code
		$protocol = isset($this->env['SERVER_PROTOCOL']) ? $this->env['SERVER_PROTOCOL'] : 'HTTP/1.1';

		header("{$protocol} {$this->responseCode}", true /* $replace */, $this->responseCode);

		// emit headers
		$headers = $this->getHeaders();

		foreach($headers as $name => $value) {
			header("{$name}: {$value}");
		}

		return true;
	}

	/**
	 * Get current response time
	 */
	public function getResponseTime() {
		return round(microtime(true) - $this->responseStart, 3);
	}

	/**
	 * Set response code
	 */
	public function setResponseCode($responseCode) {
		$this->responseCode = $responseCode;
	}

	/**
	 * Get response code
	 */
	public function getResponseCode() {
		return $this->responseCode;
	}

	/**
	 * Set cache duration time
	 */
	public function setCacheDuration($duration) {
		$duration = intval($duration);
		$time = time() + $duration;

		// for browser
		$this->setHeader('Expires', gmdate(self::DATE_RFC1123, $time));

		// for proxies
		$this->setHeader('Cache-Control', "max-age={$duration}");
	}

	/**
	 * Set last modification date
	 */
	public function setLastModified($lastModified) {
		if (is_string($lastModified)) {
			$lastModified = strtotime($lastModified);
		}
		else {
			$lastModified = intval($lastModified);
		}

		$this->setHeader('Last-Modified', gmdate(self::DATE_RFC1123, $lastModified));
	}

	/**
	 * Return whether HTTP client supports GZIP response compression
	 */
	public function gzipSupported() {
		$supportedEncoding = isset($this->env['HTTP_ACCEPT_ENCODING']) ? $this->env['HTTP_ACCEPT_ENCODING'] : '';

		return strpos($supportedEncoding, 'gzip') !== false;
	}

	/**
	 * Return response and set HTTP headers
	 */
	public function render() {
		$response = $this->getContent();

		// compress the response (if supported)
		if ($this->gzipSupported()) {
			$this->setHeader('Content-Encoding', 'gzip');
			$response = gzencode($response);
		}

		$this->sendHeaders();
		return $response;
	}
}