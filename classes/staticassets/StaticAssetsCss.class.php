<?php

/**
 * CSS processor
 *
 * $Id$
 */

class StaticAssetsCss implements IStaticAssetsProcessor {

	private $currentDir;

	/**
	 * Process given CSS file
	 */
	public function process($file) {
		$content = file_get_contents($file);

		// used for images / CSS embedding
		$this->currentDir = dirname($file);

		// TODO: embed CSS files included using @include

		// minify
		// @see http://www.lateralcode.com/css-minifer/
		$content = preg_replace('#\s+#', ' ', $content);
		$content = preg_replace('#/\*.*?\*/#s', '', $content);

		// minimize hex colors
		// @see http://code.google.com/p/minify/
        $content = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $content);

		// remove units from zero values (0px => 0)
		$content = preg_replace('#[^\d]0(px|em|pt|%)#', '0', $content);

		// embed GIF and PNG images in CSS
		$content = preg_replace_callback('#url\(["\']?([^)]+.(gif|png)["\']?)\)#', array($this, 'embedImageCallback'), $content);

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

		return $content;
	}

	/**
	 * Callback method used to embed GIF and PNG files in CSS
	 */
	public function embedImageCallback($matches) {
		// get full path to an image
		$imagePath = realpath($this->currentDir . '/' . $matches[1] /* image name from CSS*/);

		// encode it
		$encoded = $this->encodeImage($imagePath);

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
		$ext = end(explode('.', $imageFile));

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
}