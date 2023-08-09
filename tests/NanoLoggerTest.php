<?php

use Monolog\Logger;
use Monolog\LogRecord;
use Nano\Logger\NanoLogger;

/**
 * No-op logging handler. Keeps track of LogRecords sent to it.
 */
class TestLoggingHandler extends Monolog\Handler\SyslogHandler
{
    public ?LogRecord $lastRecord = null;
    protected function write(LogRecord $record): void
    {
        $this->lastRecord = $record;
    }
}

/**
 * @covers NanoLogger
 */
class NanoLoggerTest extends \Nano\NanoBaseTest
{
    public function testGetLogger(): void
    {
        $logger = NanoLogger::getLogger(name: __CLASS__, extraFields: ['foo'=>'bar']);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals(__CLASS__, $logger->getName());

        // now, let's assert on what's getting logged
        $handler = new TestLoggingHandler(ident: 'foo');
        $logger->pushHandler($handler);

        $logger->info('Message');
        $this->assertInstanceOf(LogRecord::class, $handler->lastRecord);

        $this->assertEquals(Monolog\Level::Info, $handler->lastRecord->level);
        $this->assertEquals('Message', $handler->lastRecord->message);
        $this->assertEquals('bar', $handler->lastRecord->extra['foo']);
    }
}
