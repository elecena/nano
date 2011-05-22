<?php

/**
 * Message queue access layer
 *
 * $Id$
 */

abstract class MessageQueue {

	// debug
	protected $debug;

	// connection resource
	protected $link;

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

				//$debug->log(__METHOD__ . ' - connecting using "' . $driver . '" driver');

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
	 * Add (right push) given value to the end of given queue
	 */
	abstract public function push($queueName, $value);

	/**
	 * Get and remove (left pop) a value from the beginning of given queue
	 */
	abstract public function pop($queueName);

	/**
	 * Get number of items stored in given queue
	 */
	abstract public function getLength($queueName);

	/**
	 * Deletes given queue
	 */
	abstract public function delete($queueName);
}