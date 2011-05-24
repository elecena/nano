<?php

/**
 * Set of unit tests for MessageQueue class
 *
 * $Id$
 */

class MessageQueueTest extends PHPUnit_Framework_TestCase {

	private function getMessageQueue($driver, $settings = array()) {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$app = Nano::app($dir);

		$settings = array_merge(array(
			'driver' => $driver,
		), $settings);

		return MessageQueue::connect($app, $settings);
	}

	public function testMessageQueueFactory() {
		//$this->assertInstanceOf('CacheFile', $this->getMessageQueue('file'));
		//$this->assertInstanceOf('CacheFile', $this->getMessageQueue('FiLe'));
		$this->assertNull($this->getMessageQueue('Unknown'));
	}
}