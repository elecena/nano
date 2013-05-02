<?php

/**
 * Set of unit tests for Nano's Application core
 */

include_once(dirname(__FILE__) . '/AppTest.php');

class AppCoreTest extends AppTest {

	public function testCreateApp() {
		$this->assertInstanceOf('NanoApp', $this->app);
		$this->assertInstanceOf('Cache', $this->app->getCache());
		$this->assertInstanceOf('Config', $this->app->getConfig());
		$this->assertInstanceOf('Database', $this->app->getDatabase());
		$this->assertInstanceOf('Debug', $this->app->getDebug());
		$this->assertInstanceOf('Events', $this->app->getEvents());
		$this->assertInstanceOf('Request', $this->app->getRequest());
		$this->assertInstanceOf('Response', $this->app->getResponse());
		$this->assertInstanceOf('Router', $this->app->getRouter());

		// directories
		$this->assertEquals($this->dir, $this->app->getDirectory());
		$this->assertEquals($this->dir . '/lib', $this->app->getLibDirectory());

		// test application's library
		$libName = 'gapi';

		$this->app->addLibrary($libName);
		$this->assertContains($libName, get_include_path());
	}

	public function testIsInAppDirectory() {
		$this->assertTrue($this->app->isInAppDirectory($this->dir));
		$this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers'));
		$this->assertTrue($this->app->isInAppDirectory($this->dir . '/controllers/foo'));

		$this->assertFalse($this->app->isInAppDirectory($this->dir . '/..'));
		$this->assertFalse($this->app->isInAppDirectory($this->dir . '/../..'));
	}

	public function testCliApp() {
		$app = Nano::cli($this->dir);

		$this->assertInstanceOf('NanoCliApp', $app);
		$this->assertEquals($this->dir . '/log/script.log', $app->getDebug()->getLogFile());
		$this->assertTrue($app->getRequest()->isCLI());

		$app = Nano::cli($this->dir, 'foo');

		$this->assertEquals($this->dir . '/log/foo.log', $app->getDebug()->getLogFile());
	}

	public function testAppFactory() {
		// "simple" factory call (no extra params)
		$obj = $this->app->factory('ExampleModel');

		$this->assertInstanceOf('ExampleModel', $obj);
		$this->assertInstanceOf('NanoApp', $obj->app);
		$this->assertNull($obj->foo);
		$this->assertNull($obj->bar);

		$obj->bar = 123;
		$this->assertEquals(123, $obj->bar);

		// "complex" factory call (with extra params)
		$obj = $this->app->factory('ExampleModel', array('test'));

		$this->assertInstanceOf('ExampleModel', $obj);
		$this->assertInstanceOf('NanoApp', $obj->app);
		$this->assertEquals('test', $obj->foo);
		$this->assertNull($obj->bar);

		// "complex" factory call (with extra params)
		$obj = $this->app->factory('ExampleModel', array('test', 123));

		$this->assertEquals('test', $obj->foo);
		$this->assertEquals(123, $obj->bar);

		// test creation of not existing class
		$this->assertNull($this->app->factory('NotExistingClass'));
	}

	public function testAppGetInstance() {
		$obj = $this->app->getInstance('ExampleModel');
		$this->assertInstanceOf('ExampleModel', $obj);
		$this->assertInstanceOf('NanoApp', $obj->app);

		$this->assertNull($obj->bar);

		$obj->bar = 123;
		$this->assertEquals(123, $obj->bar);

		// this call should return reference to $obj (including $obj->bar)
		$obj2 = $this->app->getInstance('ExampleModel');
		$this->assertInstanceOf('ExampleModel', $obj2);
		$this->assertEquals(123, $obj2->bar);
	}
}