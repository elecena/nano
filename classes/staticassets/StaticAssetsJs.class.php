<?php

/**
 * JS processor
 *
 * @see https://github.com/rgrove/jsmin-php/
 *
 * $Id$
 */

class StaticAssetsJs extends StaticAssetsProcessor {

	/**
	 * Process given JS files
	 */
	protected function process(Array $files) {
		$content = '';

		foreach($files as $file) {
			$content .= file_get_contents($file);
		}

		// compress JS code
		if (!$this->inDebugMode()) {
			// use Closure compatible HTTP service?
			$closureService = $this->app->getConfig()->get('assets.closureService', false);

			if ($closureService === false) {
				ini_set('memory_limit', '256M');
				$content = JSMinPlus::minify($content);
			}
			else {
				$http = $this->app->factory('HttpClient');

				$res = $http->post($closureService, array(
					'utf8' => 'on',
					'js_code' => $content,
				));

				$content = $res->getContent();
			}
		}

		return trim($content);
	}
}