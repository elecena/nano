<?php

use Nano\Logger\NanoLogger;

class TestScript extends NanoScript
{
    private ?array $foo = null;

    protected function init(): int
    {
        // this will throw an exception
        return count($this->foo);
    }

    public function run()
    {
    }
}
class NanoScriptTest extends \Nano\NanoBaseTest
{
    public function testInitExceptions()
    {
        // register a global logging handler for easier testing
        $handler = new Nano\TestLoggingHandler(ident: 'foo');
        NanoLogger::pushHandler($handler);

        $instance = Nano::script(__DIR__ . '/..', TestScript::class);
        $this->assertInstanceOf(TestScript::class, $instance);

        // our init method failed, there should be a log message about it
        $this->assertInstanceOf(\Monolog\LogRecord::class, $handler->lastRecord);

        $this->assertEquals('TestScript::init() failed', $handler->lastRecord->message);
        $this->assertInstanceOf(TypeError::class, $handler->lastRecord->context['exception']);
    }
}
