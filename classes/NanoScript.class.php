<?php

use Nano\NanoObject;

/**
 * General interface for CLI scripts
 */
abstract class NanoScript extends NanoObject {

	const LOGFILE = 'debug';

	private $isDebug = false;

	/**
	 * @param NanoApp $app
	 */
	function __construct(NanoApp $app) {
		$this->isDebug = (bool) getenv('DEBUG');

		parent::__construct($app);
		if ($this->isInDebugMode()) {
			$this->debug->log();
			$this->debug->log('Running in debug mode');
			$this->debug->log();
		}

		$this->logger->pushProcessor(function($record) {
			$record['extra']['script'] = get_class($this);
			return $record;
		});

		$this->logger->info('Starting the script');

		try {
			$this->init();
		}
		catch(Exception $e) {
			$this->logger->error(__METHOD__ . ' failed', [
				'exception' => $e
			]);
		}
	}

	/**
	 * Setup the script
	 */
	protected function init() {}

	/**
	 * Script body
	 */
	abstract public function run();

	/**
	 * Called when the script execution is completed
	 *
	 * @param NanoApp $app
	 */
	public function onTearDown(NanoApp $app) {
		$this->logger->info('Script completed', [
			'time' => $app->getResponse()->getResponseTime() * 1000, // [ms]
		]);
	}

	/**
	 * Returns true if script is run in debug mode
	 *
	 * $ DEBUG=1 php script.php
	 *
	 * @return bool is script run in debug mode?
	 */
	protected function isInDebugMode() {
		return $this->isDebug;
	}
}
