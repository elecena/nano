<?php

namespace Nano\AppTests;

use Database;
use ExampleModel;
use Nano\Cache;
use Nano\Config;
use Nano\Debug;
use Nano\Events;
use Nano\Request;
use Nano\Response;
use Nano\Router;
use Nano\TestApp\TestModel;
use NanoApp;
use NanoCliApp;

/**
 * Set of unit tests for Nano's Application core
 */
class AppCoreTest extends AppTestBase
{
    /**
     * @covers NanoApp
     */
    public function testCreateApp()
    {
        $this->assertInstanceOf(NanoApp::class, $this->app);
        $this->assertInstanceOf(Cache::class, $this->app->getCache());
        $this->assertInstanceOf(Config::class, $this->app->getConfig());
        $this->assertInstanceOf(Database::class, $this->app->getDatabase());
        $this->assertInstanceOf(Debug::class, $this->app->getDebug());
        $this->assertInstanceOf(Events::class, $this->app->getEvents());
        $this->assertInstanceOf(Request::class, $this->app->getRequest());
        $this->assertInstanceOf(Response::class, $this->app->getResponse());
        $this->assertInstanceOf(Router::class, $this->app->getRouter());

        // directories
        $this->assertEquals($this->dir, $this->app->getDirectory());
    }

    /**
     * @covers NanoApp::isInAppDirectory
     */
    public function testIsInAppDirectory()
    {
        $this->assertTrue($this->app->isInAppDirectory($this->dir));
        $this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers'));
        $this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers/foo'));

        $this->assertFalse($this->app->isInAppDirectory($this->dir . '/..'));
        $this->assertFalse($this->app->isInAppDirectory($this->dir . '/../..'));
    }

    /**
     * @covers Nano::cli
     */
    public function testCliApp()
    {
        $app = \Nano::cli($this->dir);

        $this->assertInstanceOf(NanoCliApp::class, $app);
        $this->assertEquals($this->dir . '/log/script.log', $app->getDebug()->getLogFile());

        $app = \Nano::cli($this->dir, 'foo');

        $this->assertEquals($this->dir . '/log/foo.log', $app->getDebug()->getLogFile());
    }

    /**
     * @covers NanoApp::factory
     */
    public function testAppFactory()
    {
        // "simple" factory call (no extra params)
        $obj = $this->app->factory(ExampleModel::class);

        $this->assertInstanceOf(ExampleModel::class, $obj);
        $this->assertInstanceOf(NanoApp::class, $obj->app);
        $this->assertNull($obj->foo);
        $this->assertNull($obj->bar);

        $obj->bar = 123;
        $this->assertEquals(123, $obj->bar);

        // "complex" factory call (with extra params)
        $obj = $this->app->factory(ExampleModel::class, ['test']);

        $this->assertInstanceOf(ExampleModel::class, $obj);
        $this->assertInstanceOf(NanoApp::class, $obj->app);
        $this->assertEquals('test', $obj->foo);
        $this->assertNull($obj->bar);

        // "complex" factory call (with extra params)
        $obj = $this->app->factory(ExampleModel::class, ['test', 123]);

        $this->assertEquals('test', $obj->foo);
        $this->assertEquals(123, $obj->bar);

        // test creation of not existing class
        $this->assertNull($this->app->factory('NotExistingClass'));
    }

    /**
     * @covers NanoApp::handleException
     * @dataProvider handleExceptionDataProvider
     */
    public function testHandleException(callable $fn, string $expectedClass)
    {
        $ret = $this->app->handleException($fn);
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
