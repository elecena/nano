<?php

namespace Nano\Mq;

use Nano\ResultsWrapper;
use Nano\MessageQueue;
use NanoApp;

use Predis\Client;

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
	 *
	 * @param NanoApp $app
	 * @param array $settings
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		parent::__construct($app, $settings);

		// read settings
		$host = isset($settings['host']) ? $settings['host'] : 'localhost';
		$port = isset($settings['port']) ? $settings['port'] : 6379;
		$password = isset($settings['password']) ? $settings['password'] : null;
		$timeout = isset($settings['timeout']) ? $settings['timeout'] : 5; // Predis default is 5 sec

		// lazy connect
		$this->redis = new Client([
			'scheme' => 'tcp',
			'host'   => $host,
			'port'   => $port,
			'password' => $password,
			'timeout'=> $timeout,
			'persistent' => !empty($settings['persistent']),
		]);
	}

	/**
	 * Add (right push) given message to the end of current queue and return added message
	 *
	 * @param $message array
	 * @return ResultsWrapper
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

		$this->redis->rpush($this->getQueueKey(), $rawMsg); // RPUSH

		// return wrapped message
		return new ResultsWrapper($msg);
	}

	/**
	 * Get and remove (left pop) message from the beginning of current queue
	 */
	public function pop() {
		$rawMsg = $this->redis->lpop($this->getQueueKey()); // LPOP

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
		$this->redis->del(array(
			$this->getQueueKey(),
			$this->getLastIdKey()
		));
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
