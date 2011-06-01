<?php

/**
 * Set of unit tests for MessageQueueRedis class
 *
 * $Id$
 */

class MessageQueueRedisTest extends PHPUnit_Framework_TestCase {

	private $settings;

	public function __construct() {
		$this->settings = array(
			'driver' => 'redis',
			'host' => '89.248.171.138', /* s2 */
			'port' => 60379,
			//'pass' => 'foobared',
			'prefix' => 'test',
			'queue' => 'foo',
		);
	}

	private function getMessageQueue() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$app = Nano::app($dir);

		return MessageQueue::connect($app, $this->settings);
	}

	public function testConnect() {
		$mq = $this->getMessageQueue();

		$this->assertInstanceOf('MessageQueueRedis', $mq);
	}

	public function testQueue() {
		$mq = $this->getMessageQueue();

		// select queue to use
		$mq->useQueue($this->settings['queue']);
		
		$this->assertEquals(0, $mq->getLength());
	}
}