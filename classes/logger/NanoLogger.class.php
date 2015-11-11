<?php

namespace Nano\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;

/**
 * Nano's wrapper for Monolog
 */
class NanoLogger {

	/**
	 * List of handlers defined via \Nano\Logger\NanoLogger::pushHandler
	 *
	 * @var \Monolog\Handler\HandlerInterface[]
	 */
	static private $handlers = [];

	/**
	 * Return new instance of the Logger channne
	 *
	 * All logger will use the handlers registered via \Nano\Logger\NanoLogger::pushHandler method
	 *
	 * @param string $name channel name
	 * @return \Monolog\Logger
	 */
	static function getLogger($name) {
		$logger = new Logger($name);

		// add handlers
		$logger->setHandlers(self::$handlers);

		// modify extra fields
		$logger->pushProcessor(new WebProcessor());

		return $logger;
	}

	/**
	 * Register a new log handler
	 *
	 * @param \Monolog\Handler\HandlerInterface $handler
	 */
	static function pushHandler($handler) {
		self::$handlers[] = $handler;
	}

	/**
	 * Add a "log to file" handler
	 *
	 * @param string $dir App directory
	 * @param string $stream log file name
	 * @param int $level
	 */
	static function pushStreamHandler($dir, $stream, $level=Logger::DEBUG) {
		$stream = sprintf('%s/logs/%s.log', $dir, $stream);
		$handler = new StreamHandler($stream, $level);

		self::pushHandler($handler);
	}
}
