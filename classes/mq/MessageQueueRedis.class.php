<?php

/**
 * Message queue access layer for Redis
 *
 * Following keys are used:
 *  - lastid - stores ID of recently added message
 *  - messages - messages stored in the queue
 *
 * mq::<queue_name>::messages
 * mq::<queue_name>::lastid
 */

class MessageQueueRedis extends MessageQueue {

	// connection with server
	private $redis;

	/**
	 * Connect to Redis
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		// load php-redis library
		Nano::addLibrary('php-redis');
		Autoloader::add('Redis', 'Redis.php');

		// read settings
		$host = isset($settings['host']) ? $settings['host'] : 'localhost';
		$port = isset($settings['port']) ? $settings['port'] : 6379;
		$pass = isset($settings['pass']) ? $settings['pass'] : false;

		// lazy connect
		$this->redis = new Redis($host, $port);
		#$this->redis->debug = true;

		// authenticate (if required)
		if ($pass !== false) {
			$this->redis->auth($pass);
		}
	}

	/**
	 * Add (right push) given message to the end of current queue and return added message
	 */
	public function push($message) {
		// prepare message to be added to the queue
		$id = intval($this->redis->incr($this->getLastIdKey()));

		$msg = array(
			'id' => $id,
			'data' => $message,
		);

		// encode message
		$rawMsg = json_encode($msg);

		$this->redis->push($this->getQueueKey(), $rawMsg); // RPUSH

		// return wrapped message
		return new ResultsWrapper($msg);
	}

	/**
	 * Get and remove (left pop) message from the beginning of current queue
	 */
	public function pop() {
		$rawMsg = $this->redis->pop($this->getQueueKey(), false); // LPOP

		if (!is_null($rawMsg)) {
			// decode the message
			$msg = json_decode($rawMsg, true /* as array */);

			// return wrapped message
			return new ResultsWrapper($msg);
		}
		else {
			return false;
		}
	}

	/**
	 * Get number of items stored in the current queue
	 */
	public function getLength() {
		$length = $this->redis->llen($this->getQueueKey());

		return intval($length);
	}

	/**
	 * Cleans current queue (i.e. remove all messages)
	 */
	public function clean() {
		$this->redis->delete($this->getQueueKey());
		$this->redis->delete($this->getLastIdKey());
	}

	/**
	 * Get key used for storing queue data
	 */
	protected function getQueueKey() {
		return $this->getStorageKey('messages');
	}

	/**
	 * Get key used for storing ID of recently added message
	 */
	protected function getLastIdKey() {
		return $this->getStorageKey('lastid');
	}
}