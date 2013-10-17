<?php

/**
 * Set of unit tests for Cache redis driver
 */

include_once(dirname(__FILE__) . '/CacheTest.php');

class CacheRedisTest extends CacheTest {

	protected function getCacheInstance($settings = array()) {
		$settings = array_merge(array(
			'host' => '212.91.26.151', /* s0 */
			'port' => 60380,
		), $settings);

		return $this->getCache('redis', $settings);
	}

}