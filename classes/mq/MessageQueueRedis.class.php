<?php

/**
 * Message queue access layer for Redis
 *
 * Following keys are used:
 *  - lastid - stores ID of recently added message
 *  - messages - messages stored in the queue
 *
 * $Id$
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

		require_once 'Redis.php';

		// read settings
		$host = isset($settings['host']) ? $settings['host'] : 'localhost';
		$port = isset($settings['port']) ? $settings['port'] : 6379;
		$pass = isset($settings['pass']) ? $settings['pass'] : false;

		// lazy connect
		$this->redis = new Redis($host, $port);
		$this->redis->debug = true;

		// authenticate (if required)
		if ($pass !== false) {
			$this->redis->auth($pass);
		}
	}

	/**
	 * Add (right push) given message to the end of current queue and return added message
	 */
	public function push($message) {

	}

	/**
	 * Get and remove (left pop) message from the beginning of current queue
	 */
	public function pop() {

	}

	/**
	 * Get number of items stored in the current queue
	 */
	public function getLength() {
		$length = $this->redis->llen($this->getLastIdKey());

		return intval($length);
	}

	/**
	 * Delete given queue
	 */
	public function delete($queueName) {

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