<?php

use Nano\MessageQueue;

/**
 * Set of unit tests for MessageQueue class
 */

class MessageQueueTest extends \Nano\NanoBaseTest {

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
		$this->assertInstanceOf('Nano\Mq\MessageQueueRedis', $this->getMessageQueue('redis'));
	}
}