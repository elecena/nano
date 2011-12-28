<?php

/**
 * Set of unit tests for Nano's Application dispatcher
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/AppTest.php');

class AppDispatchTest extends AppTest {

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
		$resp = $this->app->dispatch('foo', 'search');
		$this->assertEquals(array('query' => null, 'isInternal' => true), $resp);

		$resp = $this->app->dispatch('foo', 'search', array('q' => 'foo bar'));
		$this->assertEquals(array('query' => 'foo bar', 'isInternal' => true), $resp);

		$resp = $this->app->dispatch('foo', array('json', '123'));
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
		$this->assertEquals('<h1>123</h1>', $this->app->render('foo', array('bar', '123')));

		// method returns data wrapped in JSON
		$this->assertEquals('{"id":123}', $this->app->render('foo', array('json', '123')));

		// incorrect route
		$this->assertFalse($this->app->render('foo'));
	}
}