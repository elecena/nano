<?php

/**
 * Message queue access layer
 *
 * $Id$
 */

abstract class MessageQueue {

	// debug
	protected $debug;

	// queue name
	protected $queueName;

	/**
	 * Force constructors to be protected - use MessageQueue::connect
	 */
	protected function __construct(NanoApp $app, Array $settings) {
		// use debugger from the application
		$this->debug = $app->getDebug();
	}

	/**
	 * Connect to a given message queue
	 */
	public static function connect(NanoApp $app, Array $settings) {
		$debug = $app->getDebug();

		$driver = isset($settings['driver']) ? $settings['driver'] : null;
		$instance = null;

		if (!empty($driver)) {
			$className = 'MessageQueue' . ucfirst(strtolower($driver));

			$src = dirname(__FILE__) . '/mq/' . $className . '.class.php';

			if (file_exists($src)) {
				require_once $src;

				try {
					$instance = new $className($app, $settings);
				}
				catch(Exception $e) {
					// TODO: handle exception
					//var_dump($e->getMessage());
				}
			}
		}
		else {
			$debug->log(__METHOD__ . ' - no driver specified', DEBUG::ERROR);
		}

		return $instance;
	}

	/**
	 * Use given queue
	 */
	public function useQueue($queueName) {
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
	 * Delete given queue
	 */
	abstract public function delete($queueName);
}