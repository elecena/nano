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
	 *
	 * @param array $files
	 * @return string
	 */
	protected function process(array $files) {
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
				$http = new HttpClient();
				$http->setTimeout(5);

				$res = $http->post($closureService, [
					'utf8' => 'on',
					'js_code' => $content,
				]);

				if ( ($res instanceof Nano\Http\Response) && ($res->getResponseCode() === Response::OK) ) {
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
