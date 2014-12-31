<?php

use Nano\Debug;
use Nano\Response;

/**
 * JS processor
 *
 * @see https://github.com/rgrove/jsmin-php/
 * @see https://developers.google.com/closure/compiler/docs/api-tutorial1
 * @see http://marijnhaverbeke.nl/uglifyjs
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
				$content = $this->compressWithJSMin($content);
			}
			else {
				/* @var $http HttpClient */
				$http = $this->app->factory('HttpClient');
				$http->setTimeout(5);

				$res = $http->post($closureService, array(
					'utf8' => 'on',
					'js_code' => $content,
				));

				if ( ($res instanceof HttpResponse) && ($res->getResponseCode() === Response::OK) ) {
					$content = $res->getContent();
				}
				else {
					$this->app->getDebug()->log('Minifying failed!', Debug::ERROR);
					$content = '/* JSMin fallback! */' . $this->compressWithJSMin($content);
				}
			}
		}

		return trim($content);
	}

	/**
	 * Perform JS compression using JSMin
	 *
	 * @param $content string JS code to be compressed
	 * @return bool|string
	 */
	private function compressWithJSMin($content) {
		$this->app->getDebug()->log('Using JSMin to compress JavaScript code');

		ini_set('memory_limit', '256M');
		$compressed = JSMinPlus::minify($content);

		return is_string($compressed) ? $compressed : $content;
	}
}
