<?php

/**
 * Set of unit tests for Router class
 *
 * $Id$
 */

class RouterTest extends PHPUnit_Framework_TestCase {

	private $app;

	private function getRouter() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);

		return new Router($this->app);
	}

	public function testGetPathPrefix() {
		$router = $this->getRouter();

		$this->assertEquals('/site/', $router->getPathPrefix());
	}

	public function testSanitize() {
		$router = $this->getRouter();

		$this->assertEquals('foobar', $router->sanitize('foobar'));
		$this->assertEquals('foo-bar', $router->sanitize('foo/bar'));
		$this->assertEquals('foobar-123', $router->sanitize('foobar 123'));
		$this->assertEquals('foo-bar', $router->sanitize('foo bar'));
		$this->assertEquals('foo-bar', $router->sanitize('foo bar $'));
		$this->assertEquals('foo-bar', $router->sanitize('foo $ bar'));
		$this->assertEquals('foo-bar', $router->sanitize('foo  bar'));
		$this->assertEquals('foo-bar', $router->sanitize(' foo bar '));

		$this->assertEquals('aez-test', $router->sanitize('�� test-'));
	}

	public function testLink() {
		$router = $this->getRouter();

		// home URL: http://example.org/site/
		$this->assertEquals('/site/', $router->link(''));
		$this->assertEquals('/site/foo/bar', $router->link('foo/bar'));
		$this->assertEquals('/site/foo/bar', $router->link('/foo/bar/'));
		$this->assertEquals('/site/foo/bar?q=test', $router->link('foo/bar/', array('q' => 'test')));
	}

	public function testExternalLink() {
		$router = $this->getRouter();

		$this->assertEquals('http://example.org/site/', $router->externalLink('/'));
		$this->assertEquals('http://example.org/site/foo/bar?q=test&foo=1', $router->externalLink('/foo/bar/', array('q' => 'test', 'foo' => 1)));
	}

	public function testLastRoute() {
		$router = $this->getRouter();
		$request = $this->app->getRequest();

		$this->assertNull($router->getLastRoute($request));

		// $config['index'] = '/foo/index';
		$request->setPath('/');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array('index')), $router->getLastRoute());

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

		$request->setPath('/foo/bar/314-51/_test');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'bar', 'params' => array('314-51', '_test')), $router->getLastRoute());

		$request->setPath('/foo/test');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array('test')), $router->getLastRoute());

		// unroutable requests
		$request->setPath('/bar/_test/123');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/bar/apiBar/123');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));
	}

	public function testMaps() {
		$router = $this->getRouter();
		$request = $this->app->getRequest();

		// test route mapping
		$router->map('/test', '/foo/bar/123');
		$router->map('show/*', '/foo/bar/*');
		$router->map('show', '/foo/');
		$router->map('show/abc', '/foo/test');

		// home page routing
		$router->map('', '/foo/123');

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

		$request->setPath('/show/abc');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array('test')), $router->getLastRoute());

		$request->setPath('/');
		$router->route($request);
		$this->assertEquals(array('module' => 'foo', 'method' => 'route', 'params' => array('123')), $router->getLastRoute());

		// empty path mapping
		$router->map('', '');

		$request->setPath('/');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));
	}
}