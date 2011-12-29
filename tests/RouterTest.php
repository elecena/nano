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
		$this->assertEquals('foo-bar', $router->sanitize('foo - bar'));
		$this->assertEquals('foobar-123', $router->sanitize('foobar 123'));
		$this->assertEquals('foo-bar', $router->sanitize('foo bar'));
		$this->assertEquals('foo-bar', $router->sanitize('foo bar $'));
		$this->assertEquals('foo-bar', $router->sanitize('foo $ bar'));
		$this->assertEquals('foo-bar', $router->sanitize('foo  bar'));
		$this->assertEquals('foo-bar', $router->sanitize(' foo bar '));

		// utf
		$this->assertEquals('aaa', $router->sanitize('aąa'));
		$this->assertEquals('zazolc-gesla-jazn', $router->sanitize('zażółć gęślą jaźń'));
	}

	public function testFormatUrl() {
		$router = $this->getRouter();

		// home URL: http://example.org/site/
		$this->assertEquals('/site/', $router->formatUrl(''));
		$this->assertEquals('/site/foo/bar', $router->formatUrl('foo/bar'));
		$this->assertEquals('/site/foo/bar', $router->formatUrl('/foo/bar/'));
		$this->assertEquals('/site/foo/bar', $router->formatUrl('foo', 'bar'));
		$this->assertEquals('/site/foo/bar?q=test', $router->formatUrl('foo/bar/', array('q' => 'test')));
		$this->assertEquals('/site/foo/bar?q=test', $router->formatUrl('foo', 'bar', array('q' => 'test')));
		$this->assertEquals('/site/foo/bar/test?q=test&foo=bar+test', $router->formatUrl('foo', 'bar', 'test', array('q' => 'test', 'foo' => 'bar test')));
	}

	public function testFormatFullUrl() {
		$router = $this->getRouter();

		$this->assertEquals('http://example.org/site/', $router->formatFullUrl('/'));
		$this->assertEquals('http://example.org/site/foo/bar?q=test&foo=1', $router->formatFullUrl('/foo/bar/', array('q' => 'test', 'foo' => 1)));
		$this->assertEquals('http://example.org/site/foo/bar/test?q=test&foo=bar+test', $router->formatFullUrl('foo', 'bar', 'test', array('q' => 'test', 'foo' => 'bar test')));
	}

	public function testLastRoute() {
		$router = $this->getRouter();
		$request = $this->app->getRequest();

		$this->assertNull($router->getLastRoute($request));

		// $config['index'] = '/foo/index';
		$request->setPath('/');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array('index')), $router->getLastRoute());

		$request->setPath('/bar');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/foo');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array()), $router->getLastRoute());

		$request->setPath('/foo/bar');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array()), $router->getLastRoute());

		$request->setPath('/foo/bar/31451');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array('31451')), $router->getLastRoute());

		$request->setPath('/foo/bar/31451/test');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array('31451', 'test')), $router->getLastRoute());

		$request->setPath('/foo/bar/314-51/_test');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array('314-51', '_test')), $router->getLastRoute());

		$request->setPath('/foo/test');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array('test')), $router->getLastRoute());

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
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array(123)), $router->getLastRoute());

		$request->setPath('/test/123');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));

		$request->setPath('/show/456');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'bar', 'params' => array(456)), $router->getLastRoute());

		$request->setPath('/show');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array()), $router->getLastRoute());

		$request->setPath('/show/abc');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array('test')), $router->getLastRoute());

		$request->setPath('/');
		$router->route($request);
		$this->assertEquals(array('controller' => 'foo', 'method' => 'route', 'params' => array('123')), $router->getLastRoute());

		// empty path mapping
		$router->map('', '');

		$request->setPath('/');
		$router->route($request);
		$this->assertNull($router->getLastRoute($request));
	}
}