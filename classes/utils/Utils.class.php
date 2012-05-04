<?php

/**
 * nanoPortal utilities class
 *
 * $Id$
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

	/**
	 * Converts given value to encoded string
	 *
	 * @see http://programanddesign.com/php/base62-encode/
	 */
	static public function baseEncode($val) {
		$chars = self::CHARS;
		$base = strlen($chars);
		$str = '';
		do {
			$m = bcmod($val, $base);
			$str = $chars{$m} . $str;
			$val = bcdiv(bcsub($val, $m), $base);
		}
		while(bccomp($val,0)>0);
		return $str;
	}

	/**
	 * Converts given encoded string to a value
	 *
	 * @see http://programanddesign.com/php/base62-encode/
	 */
	static public function baseDecode($str) {
		$base = strlen(self::CHARS);
		$len = strlen($str);
		$val = 0;
		$arr = array_flip(str_split(self::CHARS));
		for($i = 0; $i < $len; ++$i) {
			$val = bcadd($val, bcmul($arr[$str[$i]], bcpow($base, $len-$i-1)));
		}
		return $val;
	}
}