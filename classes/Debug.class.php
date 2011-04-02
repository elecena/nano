<?php

/**
 * Debugging
 *
 * $Id$
 */

class Debug {

	// log files directory
	private $dir;

	// file log enabled
	private $logEnabled;

	// log file (full path)
	private $logFile;

	// timestamp when object was created
	private $start;

	/**
	 * Set directory for log files and log file name
	 */
	public function __construct($dir, $logFile = 'debug') {
		$this->dir = $dir;
		$this->setLogFile($logFile);
		$this->enableLog();

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
	public function log($msg, $level = 7) {
		// check if logging is enabled
		if ($this->logEnabled == false) {
			return false;
		}

		// get "delta" timestamp to log
		$delta = round(microtime(true /* get_as_float */) - $this->start, 4);

		// clear the message
		$msg = trim($msg);

		// line to be added
		$msgLine = "{$delta}: {$msg}\n";

		// TODO: check level threshold

		// log to file
		file_put_contents($this->getLogFile(), $msgLine, FILE_APPEND | LOCK_EX);

		return true;
	}
}