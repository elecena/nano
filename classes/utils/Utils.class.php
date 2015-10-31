<?php

/**
 * nanoPortal utilities class
 */

class Utils {

	// @see http://snook.ca/archives/php/url-shortener#c63363
	const CHARS = 'Ts87HNB2US1dxhgMWCpAKmRXO0rnG4lDZkcFLqutzEYbfv6JQo3Pea5iw9VyjI';

	/**
	 * Creates temporary file and returns its name
	 *
	 * @see http://www.php.net/manual/en/function.tempnam.php
	 */
	static public function getTempFile() {
		return tempnam(false /* use system default */, 'nano');
	}

	/**
	 * Creates PID file
	 */
	static public function createPidFile($pidFile) {
		file_put_contents($pidFile, getmypid() . "\n");
	}
}