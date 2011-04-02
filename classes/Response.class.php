<?php

/**
 * Handles response (sets HTTP headers, wraps output content)
 *
 * $Id$
 */

class Response {

	// HTML to be returned to the client
	private $content;

	// key / value list of HTTP headers to be emitted
	private $headers = array();

	// response HTTP code (default to 200 OK)
	private $responseCode = 200;

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
}