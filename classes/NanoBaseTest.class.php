<?php

namespace Nano;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Monolog\LogRecord;
use Monolog\Handler\SyslogHandler;

/**
 * Base class for PHPUnit-based unit tests
 *
 * @codeCoverageIgnore
 */
class NanoBaseTest extends TestCase
{
    protected \NanoApp $app;

    /* @see https://github.com/postmanlabs/httpbin */
    const HTTPBIN_HOST = 'http://0.0.0.0:5555';

    protected function setUp(): void
    {
        // use the current working directory where "./composer.phar test" is run
        $dir = getcwd();
        $this->app = \Nano::app($dir);
    }

    /**
     * Creates a Database class instance that will mock results of a given query
     *
     * @param array $result
     * @param callable|null $onQuery optional callback
     * @return NanoDatabaseMock
     */
    protected function getDatabaseMock(array $result = [], ?callable $onQuery = null): NanoDatabaseMock
    {
        $mock = new NanoDatabaseMock($this->app);

        $mock->setOnQueryCallback($onQuery);
        $mock->setResult($result);

        return $mock;
    }

    /**
     * Creates a mock of NanoApp with a given method mocked
     * @param string $method
     * @param mixed $value
     * @return MockObject
     */
    protected function getNanoAppMock(string $method, $value): MockObject
    {
        $mock = $this->createMock(\NanoApp::class);
        $mock->method($method)->willReturn($value);

        return $mock;
    }

    /**
     * @param NanoObject $obj
     * @param MockObject $mock
     */
    protected function setNanoAppMock(NanoObject $obj, MockObject $mock)
    {
        $reflection = new \ReflectionClass($obj);
        $reflection_property = $reflection->getProperty('app');
        // setAccessible() is deprecated in PHP 8.5 and no longer needed since PHP 8.1
        // All properties are accessible via reflection by default

        $reflection_property->setValue($obj, $mock);
    }
}


/**
 * No-op logging handler. Keeps track of LogRecords sent to it.
 */
class TestLoggingHandler extends SyslogHandler
{
    public ?LogRecord $lastRecord = null;
    protected function write(LogRecord $record): void
    {
        $this->lastRecord = $record;
    }
}
