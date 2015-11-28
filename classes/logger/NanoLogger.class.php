<?php

namespace Nano\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// processors
use Monolog\Processor\WebProcessor;
use Nano\Logger\Processors\ExceptionProcessor;
use Nano\Logger\Processors\RequestIdProcessor;

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
	 * Return new instance of the Logger channel
	 *
	 * All logger will use the handlers registered via \Nano\Logger\NanoLogger::pushHandler method
	 *
	 * @param string $name channel name
	 * @param array $extraFields fields to be added to every message sent from this logger
	 * @return \Monolog\Logger
	 */
	static function getLogger($name, Array $extraFields = []) {
		$logger = new Logger($name);

		// add handlers
		$logger->setHandlers(self::$handlers);

		// add extra fields
		$logger->pushProcessor(new WebProcessor());
		$logger->pushProcessor(new ExceptionProcessor());
		$logger->pushProcessor(new RequestIdProcessor());

		// add per-logger extra fields
		if (!empty($extraFields)) {
			$logger->pushProcessor(function(array $record) use ($extraFields) {
				$record['extra'] = array_merge($record['extra'], $extraFields);
				return $record;
			});
		}

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
		$handler = new RotatingFileHandler($stream, 0 /* $maxFiles */, $level);

		self::pushHandler($handler);
	}
}
