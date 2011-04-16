<?php

/**
 * Debugging
 *
 * $Id$
 */

class Debug {

	// log level constants
	const ERROR = 1;
	const WARNING = 2;
	const NOTICE = 3;
	const DEBUG = 4;
	const INFO = 5;

	// log files directory
	private $dir;

	// file log enabled
	private $logEnabled;

	// log file (full path)
	private $logFile;

	// log messages level threshold
	private $logThreshold;

	// timestamp when object was created
	private $start;

	/**
	 * Set directory for log files and log file name
	 */
	public function __construct($dir, $logFile = 'debug') {
		$this->dir = $dir;
		$this->setLogFile($logFile);
		$this->enableLog();

		// log everything
		$this->setLogThreshold(self::INFO);

		$this->start = microtime(true /* get_as_float */);
	}

	/**
	 * Enable logging to a file
	 */
	public function enableLog() {
		$this->logEnabled = true;
	}

	/**
	 * Disable logging to a file
	 */
	public function disableLog() {
		$this->logEnabled = false;
	}

	/**
	 * Set log threshold (0 - log nothing, 5 - log everything)
	 */
	public function setLogThreshold($logThreshold) {
		$this->logThreshold = intval($logThreshold);
	}

	/**
	 * Set log file name
	 */
	public function setLogFile($logFile) {
		$this->logFile = $this->dir . '/' . basename($logFile) . '.log';
	}

	/**
	 * Get log location (full path)
	 */
	public function getLogFile() {
		return $this->logFile;
	}

	/**
	 * Clears current log file
	 */
	public function clearLogFile() {
		$file = $this->getLogFile();

		if (file_exists($file)) {
			unlink($file);
		}
	}

	/**
	 * Log given message to log file
	 */
	public function log($msg, $level = 5) {
		// check if logging is enabled
		if ($this->logEnabled == false) {
			return false;
		}

		// check debug threshold
		if ($this->logThreshold < $level) {
			return false;
		}

		// timestamp
		$timestamp = $this->getTimestamp();

		// clear the message
		$msg = trim($msg);

		// line to be added
		$msgLine = "{$timestamp}: {$msg}\n";

		// log to file
		file_put_contents($this->getLogFile(), $msgLine, FILE_APPEND | LOCK_EX);

		return true;
	}

	/**
	 * Get timestamp to be added before log message
	 */
	private function getTimestamp() {
		$timestamp = microtime(true /* get_as_float */) - $this->start;
		$timestamp = sprintf('%.3f', $timestamp);

		return $timestamp;
	}
}