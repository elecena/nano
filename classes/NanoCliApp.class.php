<?php

use Nano\Logger\NanoLogger;

/**
 * Class for representing nanoPortal's application for command line interface
 */

class NanoCliApp extends NanoApp
{
    /**
     * Create application based on given config
     */
    public function __construct($dir, $configSet = 'default', $logFile = 'script')
    {
        parent::__construct($dir, $configSet, $logFile);

        // log to a file
        NanoLogger::pushStreamHandler($dir, $logFile);

        // run bootstrap file - web application runs bootstrap from app.php
        $bootstrapFile = $this->getDirectory() . '/config/bootstrap.php';

        if (is_readable($bootstrapFile)) {
            $app = $this;
            require $bootstrapFile;
        }
    }

    /**
     * Wrap your script logic with the method below to exceptions logging added.
     */
    public function handleException(callable $fn, ?callable $handler = null)
    {
        try {
            return $fn();
        } catch (\Throwable $ex) {
            // log the exception
            $logger = NanoLogger::getLogger('nano.app.exception');
            $logger->error($ex->getMessage(), [
                'exception' => $ex,
            ]);

            if (is_callable($handler)) {
                $handler($ex);
                return $ex;
            }

            echo get_class($ex) . ": {$ex->getMessage()}\n{$ex->getTraceAsString()}\n";
            die(1);
        }
    }
}
