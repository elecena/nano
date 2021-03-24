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
}
