<?php

namespace Nano\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// processors
use Monolog\Processor\WebProcessor;
use Macbre\Logger\Processors\ExceptionProcessor;
use Macbre\Logger\Processors\RequestIdProcessor;

/**
 * Nano's wrapper for Monolog
 */
class NanoLogger
{
    /**
     * List of handlers defined via \Nano\Logger\NanoLogger::pushHandler
     *
     * @var \Monolog\Handler\HandlerInterface[]
     */
    private static $handlers = [];

    /**
     * Return new instance of the Logger channel
     *
     * All logger will use the handlers registered via \Nano\Logger\NanoLogger::pushHandler method
     *
     * @param string $name channel name
     * @param array $extraFields fields to be added to every message sent from this logger
     * @return \Monolog\Logger
     */
    public static function getLogger($name, array $extraFields = [])
    {
        $logger = new Logger($name);

        // add handlers
        $logger->setHandlers(self::$handlers);

        // add extra fields
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new ExceptionProcessor());
        $logger->pushProcessor(new RequestIdProcessor());

        // CLI commands
        // TODO: move to macbre/monolog-utils as CLIProcessor
        if (!isset($_SERVER['REQUEST_URI'])) {
            $scriptName = rtrim(getcwd(), '/') . '/' . ltrim($_SERVER['PHP_SELF'], '/');

            $logger->pushProcessor(function (array $record) use ($scriptName) {
                $record['extra']['script'] = $scriptName;
                return $record;
            });
        }

        // add per-logger extra fields
        if (!empty($extraFields)) {
            $logger->pushProcessor(function (array $record) use ($extraFields) {
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
    public static function pushHandler($handler)
    {
        self::$handlers[] = $handler;
    }

    /**
     * Add a "log to file" handler
     *
     * @param string $dir App directory
     * @param string $stream log file name
     * @param int $level
     */
    public static function pushStreamHandler($dir, $stream, $level=Logger::DEBUG)
    {
        $stream = sprintf('%s/logs/%s.log', $dir, $stream);

        if (is_writable(dirname($stream))) {
            $handler = new RotatingFileHandler($stream, 0 /* $maxFiles */, $level);
            self::pushHandler($handler);
        }
    }
}
