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

		// don't minify already minified files
		if (strpos($file, '.min.js') === false) {
			$content = JSMinPlus::minify($content);
		}

		return trim($content);
	}
}