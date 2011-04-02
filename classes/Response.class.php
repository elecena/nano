<?php

/**
 * Handles response (sets HTTP headers, wraps output content)
 *
 * $Id$
 */

class Response {

	// HTML to be returned to the client
	private $content;

	/**
	 * Set output's content
	 */
	public function setContent($content) {
		// handle output's wrappers
		if ($content instanceof Output) {
			$this->content = $content->render();
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
}