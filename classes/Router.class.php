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
		$pathParts = explode(self::SEPARATOR, trim($request->getPath(), self::SEPARATOR));

		/*
		 * Parse path /product/show/123/456 to:
		 *  - module name: product
		 *  - method: show
		 *  - parameters: 123, 456
		 */

		// default module's method used for routing
		$methodName = 'route';
		$methodParams = array_fill(0, 5, null);

		switch (count($pathParts)) {
			// empty path: /
			case 0:
				// use default module
				$moduleName = '';
				break;

			// module name only: /product
			case 1:
				$moduleName = $pathParts[0];
				break;

			// module and method name: /product/bar (with parameters)
			case 2:
			default:
				$moduleName = $pathParts[0];
				$methodName = $pathParts[1];

				$methodParams = array_slice($pathParts, 2) + $methodParams;
		}

		// sanitize and normalize
		$moduleName = ucfirst(strtolower($moduleName));
		$methodName = strtolower($methodName);

		#var_dump(array($moduleName, $methodName, $methodParams));

		// default value - means 404
		$ret = null;

		// call selected module and method (with parameters)
		$module = $this->app->getModule($moduleName);

		if (!empty($module)) {
			// call selected method, otherwise call route method
			if (!is_callable(array($module, $methodName))) {
				$methodName = 'route';
			}

			$ret = call_user_func_array(array($module, $methodName), $methodParams);
		}

		return $ret;
	}
}