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
		$this->assertInstanceOf('Router', $this->app->getRouter());
		$this->assertInstanceOf('Config', $this->app->getConfig());

		// directories
		$this->assertEquals($this->dir, $this->app->getDirectory());
		$this->assertEquals($this->dir . '/lib', $this->app->getLibDirectory());

		// test application's library
		$libName = 'gapi';

		$this->app->addLibrary($libName);
		$this->assertContains($libName, get_include_path());
	}

	public function testAppFactory() {
		$obj = $this->app->factory('ExampleModel');

		$this->assertInstanceOf('ExampleModel', $obj);
		$this->assertInstanceOf('NanoApp', $obj->app);

		// test creation of not existing class
		$this->assertNull($this->app->factory('NotExistingClass'));
	}

	public function testModules() {
		$this->assertEquals(array('Foo'), $this->app->getModules());

		$obj = $this->app->getModule('Foo');

		$this->assertInstanceOf('FooModule', $obj);
		$this->assertEquals(array('id' => 123), $obj->bar('123'));

		// test creation of not existing module
		$this->assertNull($this->app->getModule('NotExistingModule'));
	}

	public function testAppConfig() {
		$config = $this->app->getConfig();

		$this->assertEquals($this->dir . '/config', $config->getDirectory());

		$this->assertEquals('value', $config->get('test'));
		$this->assertEquals(array('driver' => 'file', 'options' => array()), $config->get('cache'));
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

		$this->assertEquals(array('driver' => 'redis'), $config->get('cache'));
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

	public function testCache() {
		$this->setUp();
		$cache = $this->app->getCache();

		$this->assertInstanceOf('CacheFile', $cache);

		$key = 'foo';
		$value = array(
			'test' => true,
			'mixed' => array(1,2,3),
			'pi' => 3.1415
		);

		$this->assertTrue($cache->set($key, $value, 60));
		$this->assertTrue($cache->exists($key));
		$this->assertEquals($value, $cache->get($key));

		$cache->delete($key);

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));

		// mixed keys
		$key = array('foo', 'bar', 'test');

		$this->assertTrue($cache->set($key, $value, 60));
		$this->assertTrue($cache->exists($key));
		$this->assertEquals($value, $cache->get($key));

		$cache->delete($key);

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));

		// handling of non-existing keys
		$key = 'notExistingKey';

		$this->assertFalse($cache->exists($key));
		$this->assertNull($cache->get($key));
		$this->assertEquals($value, $cache->get($key, $value));
	}

	public function testRoute() {
		$router = new Router($this->app);
		$request = $this->app->getRequest();

		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/bar');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/foo');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array()), $router->getLastRoute());

		$request->setPath('/foo/bar');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array()), $router->getLastRoute());

		$request->setPath('/foo/bar/31451');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array('31451')), $router->getLastRoute());

		$request->setPath('/foo/bar/31451/test');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array('31451', 'test')), $router->getLastRoute());

		$request->setPath('/foo/test');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array()), $router->getLastRoute());
	}

	public function testRouteMaps() {
		$router = new Router($this->app);
		$request = $this->app->getRequest();

		// test route mapping
		$router->map('/test', '/foo/bar/123');
		$router->map('show/*', '/foo/bar/*');
		$router->map('show', '/foo/');

		$request->setPath('/test');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array(123)), $router->getLastRoute());

		$request->setPath('/test/123');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/show/456');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array(456)), $router->getLastRoute());

		$request->setPath('/show');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array()), $router->getLastRoute());
	}

	public function testRoutePrefix() {
		$router = new Router($this->app);
		$request = $this->app->getRequest();

		// prefix routing
		$router = new Router($this->app, 'api' /* $prefix */);

		// not existing route (no apiRouter method defined)
		$request->setPath('/foo');
		$resp = $router->route($request);
		$this->assertNull($resp);
		$this->assertNull($router->getLastRoute());

		// routing for apiBar method
		$request->setPath('/foo/bar/123');
		$resp = $router->route($request);
		$this->assertEquals(array('id' => 123, 'api' => true, 'query' => 'lm317'), $resp);
		$this->assertEquals(array('module' => 'foo', 'method' => 'apiBar', 'params' => array('123')), $router->getLastRoute());
	}

	public function testRequest() {
		$this->setUp();
		$request = $this->app->getRequest();

		$this->assertTrue($request->wasPosted());
		$this->assertEquals('lm317', $request->get('q'));
		$this->assertEquals('/foo/test/', $request->getPath());
		$this->assertEquals($this->ip, $request->getIP());
	}

	public function testApi() {
		$api = new Api($this->app);
		$resp = $api->call('/foo/bar/456', array(
			'q' => 'foobar',
		));

		$this->assertEquals(array('id' => 456, 'api' => true, 'query' => 'foobar'), $resp);
	}
}