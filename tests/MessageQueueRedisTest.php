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
			'pass' => 'foobared',
			'prefix' => 'test',
			'queue' => 'foo',
		);
	}

	private function getMessageQueue() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$app = Nano::app($dir);

		$mq = MessageQueue::connect($app, $this->settings);

		// select queue to use
		$mq->useQueue($this->settings['queue']);

		return $mq;
	}

	public function testConnect() {
		$mq = $this->getMessageQueue();

		$this->assertInstanceOf('MessageQueueRedis', $mq);
	}

	public function testQueuePopPushLength() {
		$mq = $this->getMessageQueue();

		// cleanup after previous test runs
		$mq->clean();

		$this->assertEquals(0, $mq->getLength());

		// add and check a message
		$msgPushed = $mq->push('foo');

		$this->assertEquals(1, $mq->getLength());

		// get message back from queue
		$msgPopped = $mq->pop();

		$this->assertEquals(0, $mq->getLength());
		$this->assertEquals(1, $msgPushed->getId());
		$this->assertEquals(1, $msgPopped->getId());
		$this->assertEquals('foo', $msgPopped->getData());

		// "multi" pop
		$msgPushed = $mq->push('foo');
		$msgPushed = $mq->push('foo');
		$msgPushed = $mq->push('foo');

		$this->assertEquals(3, $mq->getLength());

		// test message ID
		$msgPopped = $mq->pop();
		$this->assertEquals(4, $msgPopped->getId());

		// remove the queue
		$mq->clean();

		$this->assertEquals(0, $mq->getLength());
		$this->assertFalse($mq->pop());
	}

	public function testQueueMultiPopPush() {
		$mq = $this->getMessageQueue();

		// cleanup after previous test runs
		$mq->clean();

		$length = 10;

		// add set of messages
		for($n=0; $n < $length; $n++) {
			$msg = array(
				'foo' => $n,
				'test' => false,
			);
			$mq->push($msg);
		}

		$this->assertEquals($length, $mq->getLength());

		// read them
		for($n=$length - 1; $n >= 0; $n--) {
			$msg = $mq->pop();

			$this->assertEquals($n, $mq->getLength());
			$this->assertEquals($n+1, $msg->getId());

			$this->assertEquals(array('foo' => $n, 'test' => false), $msg->getData());
		}

		// queue is now empty
		$this->assertFalse($mq->pop());
	}
}