<?php

use Nano\Logger\NanoLogger;

class FailingTestScript extends NanoScript
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

class WorkingTestScript extends FailingTestScript
{
    public static ?Throwable $throw = null;

    protected function init(): int
    {
        return 0;
    }

    /**
     * @return int
     * @throws Throwable
     */
    public function run(): int
    {
        if (self::$throw) {
            throw self::$throw;
        }
        return 42;
    }
}

class NanoScriptTest extends \Nano\NanoBaseTest
{
    public function testFailingTestScriptInitException()
    {
        // register a global logging handler for easier testing
        $handler = new Nano\TestLoggingHandler(ident: 'foo');
        NanoLogger::pushHandler($handler);

        try {
            Nano::script(__DIR__ . '/..', FailingTestScript::class);
        } catch (Throwable) {
        }

        // our init method failed, there should be a log message about it
        $this->assertInstanceOf(\Monolog\LogRecord::class, $handler->lastRecord);
        $this->assertEquals(\Monolog\Level::Error, $handler->lastRecord->level);

        $this->assertEquals('FailingTestScript::init() failed', $handler->lastRecord->message);
        $this->assertInstanceOf(TypeError::class, $handler->lastRecord->context['exception']);
    }

    /**
     * @throws Throwable
     */
    public function testWorkingTestScript()
    {
        $instance = Nano::script(__DIR__ . '/..', WorkingTestScript::class);
        $this->assertInstanceOf(WorkingTestScript::class, $instance);
        $this->assertEquals(42, $instance->run());

        // make the run() method throw an exception
        $ex = new Exception('foo');
        WorkingTestScript::$throw = $ex;
        $this->expectExceptionObject($ex);
        $instance->run();
    }
}
