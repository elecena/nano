<?php

namespace Nano;

use Exception;

/**
 * Integration layer for domnikl/statsd library
 *
 * @see https://github.com/domnikl/statsd-php
 */
class Stats {

	/**
	 * @deprecated Stats will be removed
     * @throws Exception
     */
	static function getCollector() {
        throw new Exception(__METHOD__. ' is deprecated');
	}
}
