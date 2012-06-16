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
	public function processFiles(Array $files) {
		$content = '';

		foreach($files as $file) {
			$content .= file_get_contents($file);
		}

		ini_set('memory_limit', '256M');
		$content = JSMinPlus::minify($content);

		return trim($content);
	}
}