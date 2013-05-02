<?php

/**
 * Set of unit tests for MessageQueueRedis class
 */

class MessageQueueRedisTest extends PHPUnit_Framework_TestCase {

	private $settings;

	public function __construct() {
		$this->settings = array(
			'driver' => 'redis',
			#'host' => '89.248.171.138', /* s2 */
			#'host' => '89.248.166.201', /* korn */
			'host' => '94.23.170.244', /* mydevil */
			'port' => 60380,
			//'pass' => 'foobared',
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
		$mq->clean();
		$msgPushed = $mq->push('foo');
		$msgPushed = $mq->push('foo');
		$msgPushed = $mq->push('foo');

		$this->assertEquals(3, $mq->getLength());

		// test mq as FIFO
		$this->assertEquals(1, $mq->pop()->getId());
		$this->assertEquals(2, $mq->pop()->getId());

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
				'string' => "Foo bar\n123",
				'test' => false,
			);
			$mq->push($msg);
		}

		$this->assertEquals($length, $mq->getLength());

		// read them
		for($n=0; $n < $length; $n++) {
			$msg = $mq->pop();

			$this->assertEquals($length - $n - 1, $mq->getLength());
			$this->assertEquals($n+1, $msg->getId());

			$this->assertEquals(array('foo' => $n, 'string' => "Foo bar\n123", 'test' => false), $msg->getData());
		}

		// queue is now empty
		$this->assertFalse($mq->pop());
	}
}