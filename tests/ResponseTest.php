<?php

/**
 * Set of unit tests for Response class
 *
 * $Id$
 */

class ResponseTest extends PHPUnit_Framework_TestCase {

	private $app;
	private $response;

	public function setUp() {
		// use test application's directory
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);
		$this->response = new Response($this->app);
	}

	public function testHeaders() {
		$this->response->setHeader('foo', 'bar');

		$this->assertEquals('bar', $this->response->getHeader('foo'));
		$this->assertEquals(array('foo' => 'bar'), $this->response->getHeaders());

		$this->response->setHeader('foo', 'test');
		$this->response->setHeader('bar', '123');

		$this->assertEquals('test', $this->response->getHeader('foo'));
		$this->assertEquals('123', $this->response->getHeader('bar'));
		$this->assertEquals(array('foo' => 'test', 'bar' => '123'), $this->response->getHeaders());
	}

	public function testResponseCode() {
		// default response code
		$this->assertEquals(404, $this->response->getResponseCode());

		$this->response->setResponseCode(200);
		$this->assertEquals(200, $this->response->getResponseCode());
	}

	public function testSetCacheDuration() {
		$this->response->setCacheDuration(7 * 86400 /* 7 days */);

		$this->assertEquals('max-age=604800', $this->response->getHeader('Cache-Control'));
		$this->assertContains('GMT', $this->response->getHeader('Expires'));
		$this->assertContains(gmdate('H:i:s'), $this->response->getHeader('Expires'));
		$this->assertContains(gmdate('D,'), $this->response->getHeader('Expires'));
	}

	public function testSetLastModified() {
		$time = time();
		$this->response->setLastModified($time);
		$this->assertEquals(gmdate(Response::DATE_RFC1123, $time), $this->response->getHeader('Last-Modified'));

		$time = gmdate(Response::DATE_RFC1123, time() - 3600);
		$this->response->setLastModified($time);
		$this->assertEquals($time, $this->response->getHeader('Last-Modified'));

		$this->response->setLastModified(date('Y-m-d H:i:s'));
		$this->assertEquals(gmdate(Response::DATE_RFC1123), $this->response->getHeader('Last-Modified'));
	}

	public function testGzipSupported() {
		// content to be compressed
		$content = str_repeat('foo', 1024 * 64);

		$this->assertFalse($this->response->getAcceptedEncoding());
		$this->assertFalse($this->response->isCompressed());

		// loop through following cases
		// TODO: use data provider
		$cases = array(
			// none ("fake" compress method provided)
			array(
				'http_header' => 'foo',
				'accepted_encoding' => false,
			),
			// gzip
			array(
				'http_header' => 'gzip, compress',
				'accepted_encoding' => array('gzip', 'gzip'),
			),
			// gzip
			array(
				'http_header' => 'x-gzip',
				'accepted_encoding' => array('gzip', 'x-gzip'),
			),
		);

		foreach($cases as $case) {
			$this->response = new Response($this->app, array('HTTP_ACCEPT_ENCODING' => $case['http_header']));
			$this->assertEquals($case['accepted_encoding'], $this->response->getAcceptedEncoding());
		}
	}

	public function testTextResponse() {
		$text = "foo\nbar";

		$this->response->setContent($text);

		$this->assertEquals($text, $this->response->getContent());
		$this->assertEquals(array(), $this->response->getHeaders());

		// render the response (check the content and the headers)
		$this->assertEquals($text, $this->response->render());
		$this->assertNotNull($this->response->getHeader('X-Response-Time'));
	}

	public function testJSONResponse() {
		$data = array('foo' => 'bar');
		$content = Output::factory('json', $data);

		$this->response->setContent($content);

		$this->assertEquals('{"foo":"bar"}', $this->response->getContent());
		$this->assertEquals('application/json; charset=UTF-8', $this->response->getHeader('Content-type'));
	}

	/**
	 * @dataProvider ifModifiedSinceDataProvider
	 */
	public function testIfModifiedSince($lastModified, $headerValue, $expected) {
		if (!is_null($headerValue)) {
			$headers = array('HTTP_IF_MODIFIED_SINCE' => $headerValue);
		}
		else {
			$headers = array();
		}

		$request = new Request(array(), $headers);

		// mock NanoApp
		// TODO: add mockXXX method to mock certain app fields
		// mockRequest, mockDatabase, mockDebug, ...
		$app = $this->getMockBuilder('NanoApp')
			->disableOriginalConstructor()
			->setMethods(array('getRequest', 'getDebug'))
			->getMock();

		$app->expects($this->any())->method('getRequest')->will($this->returnValue($request));
		$app->expects($this->any())->method('getDebug')->will($this->returnValue($this->app->getDebug()));

		$response = new Response($app);

		if (!is_null($lastModified)) {
			$response->setLastModified($lastModified);
		}

		$this->assertEquals($expected, $response->isNotModifiedSince());
	}

	public function ifModifiedSinceDataProvider() {
		return array(
			array(null, null, false),
			array('Wed, 19 Dec 2012 14:42:24 GMT', null, false),
			array(null, 'Wed, 19 Dec 2012 14:42:24 GMT', false),
			array('Wed, 19 Dec 2012 14:42:24 GMT', 'Wed, 19 Dec 2012 13:42:24 GMT', false),

			// broken dates
			array('Wed, 19 Dec 2012 14:42:24 GMT', 'Wed, 19 Decc 2012 13:42:24 GMT', false),
			array('Wed, 19 Dec 2012 14:42:24 GMT', 'Wed, 19-12-2012 13:42:24 GMT', false),

			// ok
			array('Wed, 19 Dec 2012 14:42:24 GMT', 'Wed, 19 Dec 2012 14:42:24 GMT', true),
			array('Wed, 19 Dec 2012 14:42:24 GMT', 'Wed, 19 Dec 2012 15:42:24 GMT', true),
		);
	}
}
