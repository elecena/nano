<?php

/**
 * Set of unit tests for Nano's Application class
 *
 * $Id$
 */

class AppTest extends PHPUnit_Framework_TestCase {

	private $app;
	private $dir;
	private $ip;

	public function setUp() {
		// client's IP
		$this->ip = '66.249.66.248';

		// fake request's data
		$_REQUEST = array(
			'q' => 'lm317',
		);

		$_SERVER = array(
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/foo/test/?q=word',
			'HTTP_CLIENT_IP' => $this->ip,
		);

		$this->dir = realpath(dirname(__FILE__) . '/..');
		$this->app = Nano::app($this->dir);
	}

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
		$this->assertTrue($this->app->isInAppDirectory($this->dir . '/modules'));
		$this->assertTrue($this->app->isInAppDirectory($this->dir . '/modules/foo'));

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

	public function testModules() {
		$this->assertEquals(array('Foo', 'Static'), $this->app->getModules());

		$obj = $this->app->getModule('Foo');

		$this->assertInstanceOf('FooModule', $obj);
		$this->assertNull($obj->bar('123'));
		$this->assertEquals(array('id' => 123), $obj->getData());

		// test creation of not existing module
		$this->assertNull($this->app->getModule('NotExistingModule'));
		$this->assertNull(Module::factory($this->app, 'NotExistingModule'));

		// normalize modules names
		$this->assertInstanceOf('FooModule', $this->app->getModule('foo'));
		$this->assertInstanceOf('FooModule', $this->app->getModule('FOO'));
		$this->assertInstanceOf('FooModule', $this->app->getModule('FoO'));

		// output's format
		$module = $this->app->getModule('Foo');

		$this->assertNull($module->getFormat());

		$module->setFormat('json');
		$this->assertEquals('json', $module->getFormat());

		// render HTML
		$module->id = 123;
		$this->assertEquals('<h1>123</h1>', $module->render('bar'));

		$module->id = 'test';
		$this->assertEquals('<h1>test</h1>', $module->render('bar'));
	}

	public function testAppConfig() {
		$config = $this->app->getConfig();

		$this->assertEquals($this->dir . '/config', $config->getDirectory());

		$this->assertEquals('value', $config->get('test'));
		$this->assertEquals(array('driver' => 'file'), $config->get('cache'));
		$this->assertEquals('file', $config->get('cache.driver'));
		$this->assertEquals('123', $config->get('foo.bar'));

		$config->delete('test');

		$this->assertNull($config->get('test'));

		$config->delete('cache');

		$this->assertNull($config->get('cache'));
		$this->assertEquals('file', $config->get('cache.driver'));
	}

	public function testExtraConfig() {
		$config = $this->app->getConfig();
		$this->assertTrue($config->load('devel'));

		$this->assertEquals(array('driver' => 'redis', 'ip' => '127.0.0.1'), $config->get('cache'));
		$this->assertEquals('redis', $config->get('cache.driver'));
		$this->assertEquals('123', $config->get('foo.bar'));

		$this->assertFalse($config->load('notExistingConfigSet'));
	}

	public function testAlternativeAppConfig() {
		$app = Nano::app($this->dir, 'devel');
		$config = $app->getConfig();

		$this->assertEquals('redis', $config->get('cache.driver'));
		$this->assertNull($config->get('foo.bar'));

		$this->assertInstanceOf('CacheRedis', $app->getCache());
	}

	public function testRequest() {
		$this->setUp();
		$request = $this->app->getRequest();

		// this one will be handled by route() method
		$this->assertTrue($request->wasPosted());
		$this->assertEquals('lm317', $request->get('q'));
		$this->assertEquals('/foo/test/', $request->getPath());
		$this->assertEquals($this->ip, $request->getIP());
	}

	public function testModuleEvents() {
		$events = $this->app->getEvents();
		$module = $this->app->getModule('Foo');

		$value = 'foo';

		$this->assertTrue($events->fire('eventFoo', array(&$value)));
		$this->assertEquals('footest', $value);

		// events firing
		$this->assertEquals('footest', $module->event('foo'));
	}

	public function testDispatchRequest() {
		// method returns raw data
		$request = Request::newFromRequestURI('/foo/bar/123');
		$this->assertEquals(array('id' => 123), $this->app->dispatchRequest($request));

		// method returns data wrapped in JSON - dispatch will return raw data
		$request = Request::newFromRequestURI('/foo/json/123');
		$ret = $this->app->dispatchRequest($request);

		$this->assertEquals(array('id' => 123), $ret);

		// incorrect route
		$request = Request::newFromRequestURI('/foo');
		$this->assertFalse($this->app->dispatchRequest($request));
	}

	public function testDispatch() {
		$resp = $this->app->dispatch('/foo/search');
		$this->assertEquals(array('query' => null, 'isInternal' => true), $resp);

		$resp = $this->app->dispatch('/foo/search', array('q' => 'foo bar'));
		$this->assertEquals(array('query' => 'foo bar', 'isInternal' => true), $resp);

		$resp = $this->app->dispatch('/foo/json/123');
		$this->assertEquals(array('id' => 123), $resp);
	}

	public function testRenderRequest() {
		// method returns raw data - template will be used to render the response
		$request = Request::newFromRequestURI('/foo/bar/123');
		$this->assertEquals('<h1>123</h1>', $this->app->renderRequest($request));

		// method returns data wrapped in JSON
		$request = Request::newFromRequestURI('/foo/json/123');
		$this->assertEquals('{"id":123}', $this->app->renderRequest($request));

		// incorrect route
		$request = Request::newFromRequestURI('/foo');
		$this->assertFalse($this->app->renderRequest($request));
	}

	public function testRender() {
		// method returns raw data - template will be used to render the response
		$this->assertEquals('<h1>123</h1>', $this->app->render('/foo/bar/123'));

		// method returns data wrapped in JSON
		$this->assertEquals('{"id":123}', $this->app->render('/foo/json/123'));

		// incorrect route
		$this->assertFalse($this->app->render('/foo'));
	}
}