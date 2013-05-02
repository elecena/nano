<?php

/**
 * Set of unit tests for HttpResponse class
 */

class HttpResponseTest extends PHPUnit_Framework_TestCase {

	public function testHttpResponse() {
		$resp = new HttpResponse();

		$resp->setContent('foo');
		$this->assertEquals('foo', $resp->getContent());
		$this->assertEquals('foo', (string) $resp);

		$resp->setHeaders(array('foo' => 'bar'));
		$this->assertEquals('bar', $resp->getHeader('foo'));

		$resp->setResponseCode(200);
		$this->assertEquals(200, $resp->getResponseCode());

		$resp->setLocation('http://example.com');
		$this->assertEquals('http://example.com', $resp->getLocation());
	}
}