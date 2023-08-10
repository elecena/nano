<?php

use Monolog\Logger;
use Monolog\LogRecord;
use Nano\Logger\NanoLogger;

class NanoLoggerTest extends \Nano\NanoBaseTest
{
    public function testGetLogger(): void
    {
        // register a global logging handler for easier testing
        $handler = new Nano\TestLoggingHandler(ident: 'foo');
        NanoLogger::pushHandler($handler);

        $logger = NanoLogger::getLogger(name: __CLASS__, extraFields: ['foo'=>'bar']);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals(__CLASS__, $logger->getName());

        // now, let's assert on what's getting logged
        $logger->info('Message');
        $this->assertInstanceOf(LogRecord::class, $handler->lastRecord);

        $this->assertEquals(Monolog\Level::Info, $handler->lastRecord->level);
        $this->assertEquals('Message', $handler->lastRecord->message);
        $this->assertEquals('bar', $handler->lastRecord->extra['foo']);
    }
}
