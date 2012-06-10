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
		$cases = array(
			// none ("fake" compress method provided)
			array(
				'http_header' => 'foo',
				'accepted_encoding' => false,
				'content_encoding' => null,
			),
			// deflate
			array(
				'http_header' => 'gzip, deflate',
				'accepted_encoding' => array('deflate', 'deflate'),
				'content_encoding' => 'deflate',
				'compress_function' => 'gzdeflate',
			),
			// gzip
			array(
				'http_header' => 'gzip, compress',
				'accepted_encoding' => array('gzip', 'gzip'),
				'content_encoding' => 'gzip',
				'compress_function' => 'gzencode',
			),
			// gzip
			array(
				'http_header' => 'x-gzip',
				'accepted_encoding' => array('gzip', 'x-gzip'),
				'content_encoding' => 'x-gzip',
				'compress_function' => 'gzencode',
			),
			// compress
			array(
				'http_header' => 'compress',
				'accepted_encoding' => array('compress', 'compress'),
				'content_encoding' => 'compress',
				'compress_function' => 'gzcompress',
			),
		);

		foreach($cases as $case) {
			$this->response = new Response($this->app, array('HTTP_ACCEPT_ENCODING' => $case['http_header']));
			$this->response->setContent($content);

			$this->assertEquals($case['accepted_encoding'], $this->response->getAcceptedEncoding());

			// check the compression itself
			if (isset($case['compress_function'])) {
				$compressed = call_user_func($case['compress_function'], $content, Response::COMPRESSION_LEVEL);
				$this->assertEquals($compressed, $this->response->render());
				$this->assertEquals('Accept-Encoding', $this->response->getHeader('Vary'));
				$this->assertTrue($this->response->isCompressed());
			}
			else {
				$this->assertEquals($content, $this->response->render());
				$this->assertNull($this->response->getHeader('Vary'));
				$this->assertFalse($this->response->isCompressed());
			}

			$this->assertEquals($case['content_encoding'], $this->response->getHeader('Content-Encoding'));
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

	public function testCompressionThreshold() {
		// short response
		$content = str_repeat('foo', 100);

		$response = new Response($this->app, array('HTTP_ACCEPT_ENCODING' => 'gzip, deflate'));
		$response->setContent($content);

		$this->assertEquals($content, $response->render());
		$this->assertFalse($response->isCompressed());

		// longer response
		$content = str_repeat('foo', 2048);

		$response = new Response($this->app, array('HTTP_ACCEPT_ENCODING' => 'gzip, deflate'));
		$response->setContent($content);
		$response->render();

		$this->assertTrue($response->isCompressed());
	}

	public function testCompressionBlacklist() {
		$contentTypes = array(
			// these should not be compressed
			'image/gif' => false,
			'image/png' => false,
			'image/jpeg' => false,

			// these should be compressed
			'text/css' => true,
			'text/plain' => true,
			'text/html' => true,
		);

		$content = str_repeat('foo', 2048);

		foreach($contentTypes as $contentType => $isCompressed) {
			$response = new Response($this->app, array('HTTP_ACCEPT_ENCODING' => 'gzip, deflate'));
			$response->setContent($content);
			$response->setContentType($contentType);
			$response->render();

			$this->assertEquals($isCompressed, $response->isCompressed());
		}
	}
}