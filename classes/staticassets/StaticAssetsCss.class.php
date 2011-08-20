<?php

/**
 * CSS processor
 *
 * $Id$
 */

class StaticAssetsCss implements IStaticAssetsProcessor {

	/**
	 * Process given CSS file
	 */
	public function process($file) {
		$content = file_get_contents($file);

		// minify
		// @see http://www.lateralcode.com/css-minifer/
		$content = preg_replace('#\s+#', ' ', $content);
		$content = preg_replace('#/\*.*?\*/#s', '', $content);

		// minimize hex colors
		// @see http://code.google.com/p/minify/
        $content = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $content);

		// remove units from zero values (0px => 0)
		$content = preg_replace('#[^\d]0(px|em|pt|%)#', '0', $content);

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
}