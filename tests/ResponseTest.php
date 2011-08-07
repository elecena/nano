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
		$this->assertEquals('application/json', $response->getHeader('Content-type'));
	}
}