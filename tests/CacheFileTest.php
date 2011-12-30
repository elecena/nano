<?php

/**
 * Set of unit tests for Cache file driver
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/CacheTest.php');

class CacheFileTest extends CacheTest {

	protected function getCacheInstance($settings = array()) {
		$dir = dirname(__FILE__) . '/app/cache';

		$settings = array_merge(array(
			'directory' => $dir,
		), $settings);

		return $this->getCache('file', $settings);
	}

}