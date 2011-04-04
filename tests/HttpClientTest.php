<?php

/**
 * Set of unit tests for HttpClient class
 *
 * $Id$
 */

class HttpClientTest extends PHPUnit_Framework_TestCase {

	public function testUserAgent() {
		$client = new HttpClient();

		// check user agent
		$this->assertRegExp('#NanoPortal/' . Nano::VERSION . '#', $client->getUserAgent());
		$this->assertRegExp('#libcurl/#', $client->getUserAgent());
	}

	public function testInvalidRequest() {
		$client = new HttpClient();

		$resp = $client->get('foo://bar');

		$this->assertFalse($resp);
	}

	public function testCookiesJar() {
		$client = new HttpClient();

		// create cookie jar file
		$jarFile = dirname(__FILE__) . '/app/cache/jar';

		$client->setTimeout(0);
		$client->useCookieJar($jarFile);

		$resp = $client->get('http://www.google.com/search', array('q' => 'nano'));

		// check cookies
		clearstatcache();

		$this->assertFileExists($jarFile);
		$this->assertContains('Cookie File', file_get_contents($jarFile));

		// remove jar file
		unlink($jarFile);
		$this->assertFileNotExists($jarFile);
	}
}