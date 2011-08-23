<?php

/**
 * nanoPortal utilities class
 *
 * $Id$
 */

class Utils {

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