<?php

namespace Nano\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\LogRecord;
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
     * @var HandlerInterface[]
     */
    private static array $handlers = [];

    /**
     * Return new instance of the Logger channel
     *
     * All logger will use the handlers registered via \Nano\Logger\NanoLogger::pushHandler method
     *
     * @param string $name channel name
     * @param array $extraFields fields to be added to every message sent from this logger
     */
    public static function getLogger(string $name, array $extraFields = []): Logger
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

            $logger->pushProcessor(function (LogRecord $record) use ($scriptName) {
                $record['extra']['script'] = $scriptName;
                return $record;
            });
        }

        // add per-logger extra fields
        if (!empty($extraFields)) {
            $logger->pushProcessor(function (LogRecord $record) use ($extraFields) {
                $record['extra'] = array_merge($record['extra'], $extraFields);
                return $record;
            });
        }

        return $logger;
    }

    /**
     * Register a new log handler
     */
    public static function pushHandler(HandlerInterface $handler): void
    {
        self::$handlers[] = $handler;
    }

    /**
     * Add a "log to file" handler
     *
     * @param string $dir App directory
     * @param string $stream log file name
     */
    public static function pushStreamHandler(string $dir, string $stream, int $level=Logger::DEBUG): void
    {
        $stream = sprintf('%s/logs/%s.log', $dir, $stream);

        if (is_writable(dirname($stream))) {
            $handler = new RotatingFileHandler($stream, 0 /* $maxFiles */, $level);
            self::pushHandler($handler);
        }
    }
}
