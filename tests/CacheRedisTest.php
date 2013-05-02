<?php

/**
 * Set of unit tests for Cache redis driver
 */

include_once(dirname(__FILE__) . '/CacheTest.php');

class CacheRedisTest extends CacheTest {

	protected function getCacheInstance($settings = array()) {
		$settings = array_merge(array(
			#'host' => '89.248.171.138', /* s2 */
			#'host' => '89.248.166.201', /* korn */
			'host' => '94.23.170.244', /* mydevil */
			'port' => 60380,
		), $settings);

		return $this->getCache('redis', $settings);
	}

}