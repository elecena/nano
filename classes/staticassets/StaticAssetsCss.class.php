<?php

/**
 * CSS processor
 */

class StaticAssetsCss extends StaticAssetsProcessor {

	// embed images smaller then (size in bytes)
	const IMAGE_EMBED_THRESHOLD = 2048;

	private $currentDir;

	protected function process(Array $files) {
		$content = '';

		foreach($files as $file) {
			$content .= $this->processFile($file);
		}

		return $content;
	}

	/**
	 * Process given CSS file
	 */
	private function processFile($file) {
		$content = file_get_contents($file);

		// used for images / CSS embedding
		$this->currentDir = dirname($file);

		// embed CSS files included using @import url(css/foo.css);
		$content = preg_replace_callback('#@import url\(["\']?([^)]+.css)["\']?\);#', array($this, 'importCssCallback'), $content);

		// minify
		// @see http://www.lateralcode.com/css-minifer/
		if (!$this->inDebugMode()) {
			$content = preg_replace('#\s+#', ' ', $content);
			$content = preg_replace('#/\*.*?\*/#s', '', $content);

			// minimize hex colors
			// @see http://code.google.com/p/minify/
			$content = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $content);

			// remove units from zero values (0px => 0)
			$content = preg_replace('#([^\d]0)(px|em|pt|%)#', '$1', $content);

			// remove zeros from values within (0,1) range (0.5 => .5)
			$content = preg_replace('#([^\d])0(\.[\d]+(px|em|pt|%))#', '$1$2', $content);

			// embed GIF and PNG images in CSS
			$content = preg_replace_callback('#url\(["\']?([^)]+.(gif|png))["\']?\)#', array($this, 'embedImageCallback'), $content);

			$content = strtr(trim($content), array(
				'; ' => ';',
				': ' => ':',
				' {' => '{',
				'{ ' => '{',
				', ' => ',',
				'} ' => '}',
				';} ' => '}',
			));

			// cleanup
			$content = strtr($content, array(
				'{ ' => '{',
				'} ' => '}',
				';} ' => '}',
				';}' => '}',
				', ' => ',',
				'} .' => '}.',
			));
		}

		// fix relative paths
		$content = preg_replace_callback('#url\(["\']?([^)]+.)["\']?\)#', array($this, 'fixRelativeImagePath'), $content);

		return $content;
	}

	/**
	 * Callback method used to embed CSS files included via @import url(foo.css) statement
	 *
	 * Please note: included files must lie in the same directory as the "main" CSS file.
	 * Currently there's no support for rewritting URLs in included CSS file.
	 */
	public function importCssCallback($matches) {
		// get full path to CSS file
		$cssFile = realpath($this->currentDir . '/' . $matches[1] /* CSS file name */);

		if (file_exists($cssFile)) {
			// read external CSS file
			return file_get_contents($cssFile);
		}
		else {
			// embedding failed - don't replace
			return $matches[0];
		}
	}

	/**
	 * Callback method used to embed GIF and PNG files in CSS
	 */
	public function embedImageCallback($matches) {
		// get full path to an image
		$imageFile = realpath($this->currentDir . '/' . $matches[1] /* image name from CSS*/);

		// encode it
		if (filesize($imageFile) < self::IMAGE_EMBED_THRESHOLD) {
			$encoded = $this->encodeImage($imageFile);
		}
		else {
			$encoded = false;
		}

		if ($encoded !== false) {
			return "url({$encoded})";
		}
		else {
			// encoding failed - don't replace
			return $matches[0];
		}
	}

	/**
	 * Encode given image file using base64 (data-uri encoding)
	 *
	 * @see http://www.ietf.org/rfc/rfc2397
	 */
	public function encodeImage($imageFile) {
		if (!file_exists($imageFile)) {
			return false;
		}

		// get file's extension
		$parts = explode('.', $imageFile);
		$ext = end($parts);

		switch($ext) {
			case 'gif':
			case 'png':
				$type = $ext;
				break;

			case 'jpg':
				$type = 'jpeg';
				break;

			// not supported image type provided
			default:
				return false;
		}

		$content = file_get_contents($imageFile);
		$encoded = base64_encode($content);

		return "data:image/{$type};base64,{$encoded}";
	}

	/**
	 * Callback method used to fix relative paths to images
	 */
	public function fixRelativeImagePath($matches) {
		// base64 encoded image - ignore
		if (strpos($matches[1], 'data:') === 0) {
			return $matches[0];
		}

		// get full path to an image
		$imageFile = realpath($this->currentDir . '/' . $matches[1] /* image name from CSS*/);

		// path relative to app's root directory
		$imageUrl = $this->staticAssets->getUrlForFile($imageFile);

		if ($imageUrl !== false) {
			return "url({$imageUrl})";
		}
		else {
			// don't replace
			return $matches[0];
		}
	}
}