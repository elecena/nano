<?php


namespace Nano\AppTests;

use Nano\TestApp\TestModel;

class NanoCliAppTest extends \Nano\NanoBaseTest
{
    protected \NanoCliApp $cliApp;

    public function setUp(): void
    {
        $dir = realpath(__DIR__ . '/..');
        $this->cliApp = \Nano::cli($dir);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('handleExceptionDataProvider')]
    public function testHandleException(callable $fn, string $expectedClass)
    {
        // by passing the handler we make handleException() return the exception instead of die()
        $ret = $this->cliApp->handleException($fn, handler: function (\Throwable $ex) {});
        $this->assertInstanceOf($expectedClass, $ret);
    }

    public static function handleExceptionDataProvider(): \Generator
    {
        yield 'TypeError' => [
            function () {
                array_filter(null); // passing null here triggers the TypeError
            },
            \TypeError::class,
        ];

        yield 'Exception' => [
            function () {
                throw new \Exception('foo');
            },
            \Exception::class,
        ];

        yield 'Callable return value is returned by handleException' => [
            function () {
                return new TestModel();
            },
            TestModel::class,
        ];
    }
}
