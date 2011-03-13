<?php

/**
 * Requests router
 *
 * $Id$
 */

class Router {

	const SEPARATOR = '/';

	private $app;

	function __construct(NanoApp $app) {
		$this->app = $app;
	}

	/**
	 * Route given request
	 */
	public function route(Request $request) {
		// split path by separators
		$pathParts = explode(self::SEPARATOR, $request->getPath());
	}
}