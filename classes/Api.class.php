<?php

/**
 * Class providing access to modules' API
 *
 * $Id$
 */

class Api {

	private $app;

	function __construct(NanoApp $app) {
		$this->app = $app;
	}

	/**
	 * Return results of given API call
	 */
	public function call($path, $params = array()) {
		// create "fake" request
		$env = array(
			'REQUEST_METHOD' => 'API'
		);

		$request = new Request($params, $env);
		$request->setPath($path);

		// create router
		$router = new Router($this->app, 'api' /* $prefix */);

		// call module
		$resp = $router->route($request);

		return $resp;
	}
}