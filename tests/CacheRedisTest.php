<?php

/**
 * Set of unit tests for Cache redis driver
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/CacheTest.php');

class CacheRedisTest extends CacheTest {

	protected function getCacheInstance($settings = array()) {
		$settings = array_merge(array(
			'host' => '89.248.171.138', /* s2 */
			'port' => 60380,
		), $settings);

		return $this->getCache('redis', $settings);
	}

}