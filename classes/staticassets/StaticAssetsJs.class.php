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
	 * Process given JS file
	 */
	public function process($file) {
		$content = file_get_contents($file);

		// don't minify already minified files
		if (strpos($file, '.min.js') === false) {
			$content = JSMinPlus::minify($content);
		}

		return trim($content);
	}
}