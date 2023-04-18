<?php

namespace Nano\AppTests;

/**
 * Set of unit tests for Nano's Application core
 */
class AppCoreTest extends AppTestBase
{
    public function testCreateApp()
    {
        $this->assertInstanceOf('NanoApp', $this->app);
        $this->assertInstanceOf('Nano\Cache', $this->app->getCache());
        $this->assertInstanceOf('Nano\Config', $this->app->getConfig());
        $this->assertInstanceOf('Database', $this->app->getDatabase());
        $this->assertInstanceOf('Nano\Debug', $this->app->getDebug());
        $this->assertInstanceOf('Nano\Events', $this->app->getEvents());
        $this->assertInstanceOf('Nano\Request', $this->app->getRequest());
        $this->assertInstanceOf('Nano\Response', $this->app->getResponse());
        $this->assertInstanceOf('Nano\Router', $this->app->getRouter());

        // directories
        $this->assertEquals($this->dir, $this->app->getDirectory());
    }

    public function testIsInAppDirectory()
    {
        $this->assertTrue($this->app->isInAppDirectory($this->dir));
        $this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers'));
        $this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers/foo'));

        $this->assertFalse($this->app->isInAppDirectory($this->dir . '/..'));
        $this->assertFalse($this->app->isInAppDirectory($this->dir . '/../..'));
    }

    public function testCliApp()
    {
        $app = \Nano::cli($this->dir);

        $this->assertInstanceOf('NanoCliApp', $app);
        $this->assertEquals($this->dir . '/log/script.log', $app->getDebug()->getLogFile());

        $app = \Nano::cli($this->dir, 'foo');

        $this->assertEquals($this->dir . '/log/foo.log', $app->getDebug()->getLogFile());
    }

    public function testAppFactory()
    {
        // "simple" factory call (no extra params)
        $obj = $this->app->factory('ExampleModel');

        $this->assertInstanceOf('ExampleModel', $obj);
        $this->assertInstanceOf('NanoApp', $obj->app);
        $this->assertNull($obj->foo);
        $this->assertNull($obj->bar);

        $obj->bar = 123;
        $this->assertEquals(123, $obj->bar);

        // "complex" factory call (with extra params)
        $obj = $this->app->factory('ExampleModel', ['test']);

        $this->assertInstanceOf('ExampleModel', $obj);
        $this->assertInstanceOf('NanoApp', $obj->app);
        $this->assertEquals('test', $obj->foo);
        $this->assertNull($obj->bar);

        // "complex" factory call (with extra params)
        $obj = $this->app->factory('ExampleModel', ['test', 123]);

        $this->assertEquals('test', $obj->foo);
        $this->assertEquals(123, $obj->bar);

        // test creation of not existing class
        $this->assertNull($this->app->factory('NotExistingClass'));
    }
}
