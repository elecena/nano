<?php

namespace Nano;
use Nano\Output;
use Nano\Stats;
use Macbre\Logger\Processors\RequestIdProcessor;

/**
 * Handles response (sets HTTP headers, wraps output content)
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
	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const SERVICE_UNAVAILABLE = 503;

	// zlib compression level to be used
	const COMPRESSION_LEVEL = 9;

	// don't compress small responses
	const COMPRESSION_LENGTH_THRESHOLD = 1024;

	private $app;
	private $config;
	private $debug;
	private $stats;

	// this flag is be set when response is compressed
	private $isCompressed = false;

	// HTML to be returned to the client
	private $content;

	// key / value list of HTTP headers to be emitted
	private $headers = array();

	// response HTTP code (defaults to 404 - Not Found)
	private $responseCode = 404;

	// timestamp of Last-Modified header
	private $lastModified = false;

	// ETag header
	private $eTag = false;

	// $_SERVER global
	private $env;

	/**
	 * Set the timestamp of the response start
	 */
	public function __construct(\NanoApp $app, $env = array()) {
		$this->app = $app;
		$this->env = $env;

		$this->config = $this->app->getConfig();
		$this->debug = $this->app->getDebug();
		$this->stats = Stats::getCollector($app, 'response');

		// don't enable output buffering when in CLI
		if ($this->app->getRequest()->isCLI()) {
			return;
		}

		// gzip on PHP level is disabled
		if ($this->config->get('gzip.disabled') === true) {
			return;
		}

		$this->debug->log(__METHOD__ . " - output buffering started");
		if (!defined('NANO_TEST')) ob_start();

		// start output buffering
		$acceptedEncoding = $this->getAcceptedEncoding();

		if ($acceptedEncoding !== false && !headers_sent()) {
			ini_set("zlib.output_compression", 4096);
			$this->debug->log(__METHOD__ . " - using response compression");

			// fix for proxies
			$this->setHeader('Vary', 'Accept-Encoding');
		}
	}

	/**
	 * Set output's content
	 *
	 * @param Output|string $content content to set
	 */
	public function setContent($content) {
		// handle output's wrappers
		if ($content instanceof Output) {
			// render data
			$this->content = $content->render();

			// use proper content type
			$this->setContentType($content->getContentType());
		}
		// handle strings
		else if (is_string($content)) {
			$this->content = $content;
		}
	}

	/**
	 * Set output's content type header
	 */
	public function setContentType($contentType) {
		$this->setHeader('Content-type', $contentType);
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
		if (!is_null($value)) {
			$this->headers[$name] = $value;
		}
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
		// set some debug headers
		$this->setHeader('X-Response-Time', $this->getResponseTime()); # [sec]
		$this->setHeader('X-Request-Id', RequestIdProcessor::getRequestId());
		$this->setHeader('X-Served-By', gethostname());

		// don't emit, if already emitted :)
		if (headers_sent($file, $line)) {
			$this->debug->log(__METHOD__ . " - headers already emitted at $file:$line");
			return false;
		}

		// @see http://stackoverflow.com/a/17368552
		header_remove('X-Powered-By');

		// emit HTTP protocol and response code
		$protocol = isset($this->env['SERVER_PROTOCOL']) ? $this->env['SERVER_PROTOCOL'] : 'HTTP/1.1';

		header("{$protocol} {$this->responseCode}", true /* $replace */, $this->responseCode);
		$this->debug->log(__METHOD__ . " - HTTP {$this->responseCode}");

		// emit headers
		$headers = $this->getHeaders();

		foreach($headers as $name => $value) {
			header("{$name}: {$value}");
		}

		// stats
		$this->stats->increment("code.{$this->responseCode}");
		$this->stats->timing('time.total', round($this->getResponseTime() * 1000) /* ms */);
		$this->stats->memory('memory.total');

		return true;
	}

	/**
	 * Get current response time
	 *
	 * @return float response handling time (in seconds)
	 */
	public function getResponseTime() {
		return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
	}

	/**
	 * Set response code
	 */
	public function setResponseCode($responseCode) {
		$this->responseCode = $responseCode;

		$this->debug->log(__METHOD__ . " - {$responseCode}");
	}

	/**
	 * Get response code
	 */
	public function getResponseCode() {
		return $this->responseCode;
	}

	/**
	 * Set cache duration time
	 *
	 * @param int $duration
	 */
	public function setCacheDuration($duration) {
		$duration = intval($duration);
		$this->setHeader('Cache-Control', "public, max-age={$duration}");

		$this->debug->log(__METHOD__ . " - {$duration} sec");
	}

	/**
	 * Set last modification date
	 */
	public function setLastModified($lastModified) {
		if (is_string($lastModified)) {
			$this->lastModified = strtotime($lastModified);
		}
		else {
			$this->lastModified = intval($lastModified);
		}

		$date = gmdate(self::DATE_RFC1123, $this->lastModified);
		$this->setHeader('Last-Modified', $date);

		$this->debug->log(__METHOD__ . " - {$date}");
	}

	/**
	 * Set ETag value
	 *
	 * @param string $eTag
	 */
	public function setETag($eTag) {
		$this->eTag = $eTag;
		$this->setHeader('ETag', $eTag);

		$this->debug->log(__METHOD__ . " - {$eTag}");
	}

	/**
	 * Return whether HTTP client supports GZIP response compression
	 *
	 * Based on HTTP_Encoder class from Minify project
	 *
	 * Returns array two values, 1st is the actual encoding method, 2nd is the alias of that method to use in the Content-Encoding header
	 * (some browsers call gzip "x-gzip" etc.)
	 *
	 * @see http://www.codinghorror.com/blog/2008/10/youre-reading-the-worlds-most-dangerous-programming-blog.html
	 */
	public function getAcceptedEncoding() {
		$acceptedEncoding = isset($this->env['HTTP_ACCEPT_ENCODING']) ? $this->env['HTTP_ACCEPT_ENCODING'] : '';

		if ($acceptedEncoding === '') {
			return false;
		}

		// gzip checks (quick)
		if (0 === strpos($acceptedEncoding, 'gzip,')             // most browsers
			|| 0 === strpos($acceptedEncoding, 'deflate, gzip,') // opera
		) {
			return array('gzip', 'gzip');
		}

		// gzip checks (slow)
		if (preg_match(
				'@(?:^|,)\\s*((?:x-)?gzip)\\s*(?:$|,|;\\s*q=(?:0\\.|1))@'
				,$acceptedEncoding
				,$m)) {
			return array('gzip', $m[1]);
		}
		return false;
	}

	/**
	 * Return true if response is compressed
	 */
	public function isCompressed() {
		return $this->isCompressed;
	}

	/**
	 * Return true if the resource was NOT modified since the last time browser requested it
	 *
	 * @see http://stackoverflow.com/questions/10847157/handling-if-modified-since-header-in-a-php-script
	 */
	public function isNotModifiedSince() {
		$ifModifiedSince = $this->app->getRequest()->getHeader('If-Modified-Since');

		if (($this->lastModified !== false) && $ifModifiedSince) {
			// parse provided header
			$ifModifiedSinceTimestamp = strtotime($ifModifiedSince);

			if (($ifModifiedSinceTimestamp > 0) && ($ifModifiedSinceTimestamp >= $this->lastModified)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return true if the resource was NOT modified (using ETag + If-None-Match headers)
	 *
	 * @see http://stackoverflow.com/questions/998791/if-modified-since-vs-if-none-match
	 * @return bool
	 */
	public function ifNoneMatch() {
		$ifNoneMatch = $this->app->getRequest()->getHeader('If-None-Match');

		return is_string($this->eTag) && ($this->eTag === $ifNoneMatch);
	}

	/**
	 * Handle If-Modified-Since request header
	 *
	 * @return bool false if page was modified since
	 */
	private function handleIfModifiedSince() {
		if ($this->isNotModifiedSince() || $this->ifNoneMatch()) {
			$this->debug->log(__METHOD__ . ' - sending 304 Not Modified');

			$this->setResponseCode(self::NOT_MODIFIED);
			$this->sendHeaders();

			// don't emit anything to the client
			ob_end_clean();

			// tear down the app
			$this->app->tearDown();
			die();
		}

		return false;
	}

	/**
	 * Flush the content of the webpage and send it to the client
	 *
	 * @see http://stackoverflow.com/questions/4870697/php-flush-that-works-even-in-nginx
	 */
	public function flush() {
		if ($this->handleIfModifiedSince()) {
			return;
		}

		$this->sendHeaders();

		ob_end_flush();
		ob_flush();
		flush();
		ob_start();

		$this->debug->log(__METHOD__ . ' - done');
	}

	/**
	 * Return response and set HTTP headers
	 */
	public function render() {
		if ($this->handleIfModifiedSince()) {
			return '';
		}

		$this->sendHeaders();
		return $this->getContent();
	}
}
