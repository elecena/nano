<?php

namespace Nano;

use NanoApp;

/**
 * Message queue access layer
 */
abstract class MessageQueue {

	// key parts separator
	const SEPARATOR = '::';

	// debug
	protected $debug;

	// queue name
	protected $queueName;

	// prefix
	private $prefix;

	/**
	 * Force constructors to be protected - use MessageQueue::connect
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		// use debugger from the application
		$this->debug = $app->getDebug();

		// set prefix
		$this->prefix = isset($settings['prefix']) ? $settings['prefix'] : false;
	}

	/**
	 * Connect to a given message queue
	 *
	 * @param NanoApp $app
	 * @param array $settings
	 * @return MessageQueue
	 */
	public static function connect(NanoApp $app, Array $settings) {
		$className = sprintf('Nano\\Mq\\MessageQueue%s', ucfirst($settings['driver']));
		return new $className($app, $settings);
	}

	/**
	 * Use given queue
	 *
	 * @param string $queueName
	 */
	public function useQueue($queueName) {
		$this->debug->log(sprintf('%s: using queue "%s"', __CLASS__, $queueName));

		$this->queueName = $queueName;
	}

	/**
	 * Add (right push) given message to the end of current queue and return added message
	 */
	abstract public function push($message);

	/**
	 * Get and remove (left pop) message from the beginning of current queue
	 */
	abstract public function pop();

	/**
	 * Get number of items stored in current queue
	 */
	abstract public function getLength();

	/**
	 * Cleans current queue (i.e. remove all messages)
	 */
	abstract public function clean();

	/**
	 * Get key used for storing message queue data
	 */
	protected function getStorageKey($key) {
		// add queue name before key name
		$key = 'mq' . self::SEPARATOR . $this->queueName . self::SEPARATOR . $key;

		// add prefix (if provided)
		if ($this->prefix !== false) {
			$key = $this->prefix . self::SEPARATOR . $key;
		}

		return $key;
	}
}
