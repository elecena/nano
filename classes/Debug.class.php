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

	// timers (used by time() / timeEnd() methods)
	private $timers = array();

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
			@unlink($file);
		}
	}

	/**
	 * Log given message to log file
	 */
	public function log($msg = '', $level = 5) {
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
		$msgLine = "{$timestamp}: {$msg}";

		// log to file
		self::logToFile($this->getLogFile(), $msgLine, false /* we formatted our own timestamp */);

		return true;
	}

	/**
	 * "Statically" log to a given file
	 */
	public static function logToFile($file, $msg = '', $addTimestamp = true) {
		$prefix = ($addTimestamp === true) ? ('[' . date('Y-m-d H:i:s') . '] ') : '';

		// line to be added
		$msgLine = "{$prefix}{$msg}\n";

		// log to file
		return file_put_contents($file, $msgLine, FILE_APPEND | LOCK_EX) !== false;
	}

	/**
	 * Starts timer under given name
	 */
	public function time($timer) {
		$this->timers[$timer] = microtime(true /* get_as_float */);
	}

	/**
	 * Get elapsed time from given timer and remove it
	 */
	public function timeEnd($timer) {
		if (isset($this->timers[$timer])) {
			$time = microtime(true /* get_as_float */) - $this->timers[$timer];
			unset($this->timers[$timer]);

			return round($time, 3);
		}
		else {
			return null;
		}
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