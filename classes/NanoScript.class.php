<?php

use Nano\NanoObject;

/**
 * General interface for CLI scripts
 *
 * @codeCoverageIgnore
 */
abstract class NanoScript extends NanoObject
{
    const LOGFILE = 'debug';

    private bool $isDebug = false;
    private array $arguments = [];

    /**
     * @param NanoApp $app
     */
    public function __construct(NanoApp $app)
    {
        // pass command line arguments
        global $argv;
        $this->arguments = $argv;

        $this->isDebug = (bool) getenv('DEBUG');
        parent::__construct();

        if ($this->isInDebugMode()) {
            $this->logger->debug('Running in debug mode');
        }

        $this->logger->pushProcessor(function (\Monolog\LogRecord $record) {
            $record->extra['script_class'] = get_class($this);
            return $record;
        });

        $this->logger->info('Starting the script');

        try {
            $this->init();
        } catch (Throwable $e) {
            $this->logger->error(get_class($this) . '::init() failed', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Set up the script
     */
    protected function init()
    {
    }

    /**
     * Script body
     */
    abstract public function run();

    /**
     * Called when the script execution is completed
     */
    public function onTearDown(NanoApp $app): void
    {
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
    protected function isInDebugMode(): bool
    {
        return $this->isDebug;
    }

    /**
     * Was given option passed in command line as "--option"?
     */
    protected function hasOption(string $opt): bool
    {
        $opt = sprintf('--%s', $opt);
        return in_array($opt, $this->arguments);
    }
}
