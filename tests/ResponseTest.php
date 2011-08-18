<?php

/**
 * Set of unit tests for Response class
 *
 * $Id$
 */

class ResponseTest extends PHPUnit_Framework_TestCase {

	public function testHeaders() {
		$response = new Response();
		$response->setHeader('foo', 'bar');

		$this->assertEquals('bar', $response->getHeader('foo'));
		$this->assertEquals(array('foo' => 'bar'), $response->getHeaders());

		$response->setHeader('foo', 'test');
		$response->setHeader('bar', '123');

		$this->assertEquals('test', $response->getHeader('foo'));
		$this->assertEquals('123', $response->getHeader('bar'));
		$this->assertEquals(array('foo' => 'test', 'bar' => '123'), $response->getHeaders());
	}

	public function testResponseCode() {
		$response = new Response();

		// default response code
		$this->assertEquals(404, $response->getResponseCode());

		$response->setResponseCode(200);
		$this->assertEquals(200, $response->getResponseCode());
	}

	public function testSetCacheDuration() {
		$response = new Response();
		$response->setCacheDuration(7 * 86400 /* 7 days */);

		$this->assertEquals('max-age=604800', $response->getHeader('Cache-Control'));
		$this->assertContains('GMT', $response->getHeader('Expires'));
		$this->assertContains(gmdate('H:i:s'), $response->getHeader('Expires'));
		$this->assertContains(gmdate('D,'), $response->getHeader('Expires'));
	}

	public function testSetLastModified() {
		$response = new Response();

		$time = time();
		$response->setLastModified($time);
		$this->assertEquals(gmdate(Response::DATE_RFC1123, $time), $response->getHeader('Last-Modified'));

		$time = gmdate(Response::DATE_RFC1123, time() - 3600);
		$response->setLastModified($time);
		$this->assertEquals($time, $response->getHeader('Last-Modified'));

		$response->setLastModified(date('Y-m-d H:i:s'));
		$this->assertEquals(gmdate(Response::DATE_RFC1123), $response->getHeader('Last-Modified'));
	}

	public function testGzipSupported() {
		// content to be compressed
		$content = str_repeat('foo', 1024 * 64);

		$response = new Response();
		$this->assertFalse($response->getAcceptedEncoding());

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
			$response = new Response(array('HTTP_ACCEPT_ENCODING' => $case['http_header']));
			$response->setContent($content);

			$this->assertEquals($case['accepted_encoding'], $response->getAcceptedEncoding());

			// check the compression itself
			if (isset($case['compress_function'])) {
				$compressed = call_user_func($case['compress_function'], $content, Response::COMPRESSION_LEVEL);
				$this->assertEquals($compressed, $response->render());
			}
			else {
				$this->assertEquals($content, $response->render());
			}

			// check the headers
			$this->assertEquals('Accept-Encoding', $response->getHeader('Vary'));
			$this->assertEquals($case['content_encoding'], $response->getHeader('Content-Encoding'));
		}
	}

	public function testTextResponse() {
		$text = "foo\nbar";

		$response = new Response();
		$response->setContent($text);

		$this->assertEquals($text, $response->getContent());
		$this->assertEquals(array(), $response->getHeaders());

		// render the response (check the content and the headers)
		$this->assertEquals($text, $response->render());
		$this->assertNotNull($response->getHeader('X-Response-Time'));
	}

	public function testJSONResponse() {
		$data = array('foo' => 'bar');
		$content = Output::factory('json', $data);

		$response = new Response();
		$response->setContent($content);

		$this->assertEquals('{"foo":"bar"}', $response->getContent());
		$this->assertEquals('application/json; charset=UTF-8', $response->getHeader('Content-type'));
	}

	public function testCompressionThreshold() {
		// short response
		$content = str_repeat('foo', 100);

		$response = new Response(array('HTTP_ACCEPT_ENCODING' => 'gzip, deflate'));
		$response->setContent($content);

		$this->assertEquals($content, $response->render());
		$this->assertNull($response->getHeader('Content-Encoding'));

		// longer response
		$content = str_repeat('foo', 2048);

		$response = new Response(array('HTTP_ACCEPT_ENCODING' => 'gzip, deflate'));
		$response->setContent($content);
		$response->render();

		$this->assertEquals('deflate', $response->getHeader('Content-Encoding'));
	}
}