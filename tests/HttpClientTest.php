<?php

/**
 * Set of unit tests for HttpClient class
 */

class HttpClientTest extends PHPUnit_Framework_TestCase {

	public function testUserAgent() {
		$client = new HttpClient();

		// check user agent
		$this->assertRegExp('#NanoPortal/' . Nano::VERSION . '#', $client->getUserAgent());
		$this->assertRegExp('#libcurl/#', $client->getUserAgent());
	}

	/**
	 * @expectedException Nano\Http\ResponseException
	 */
	public function testInvalidRequest() {
		$client = new HttpClient();
		$client->get('foo://bar');
	}

	public function testCookiesJar() {
		$client = new HttpClient();

		// create cookie jar file
		$jarFile = tempnam(dirname(__FILE__) . '/app/cache', 'jar');

		$client->setTimeout(0);
		$client->useCookieJar($jarFile);

		$client->get('http://www.google.com/search', ['q' => 'nano']);

		// close HTTP session
		$client->close();

		// check cookies
		$this->assertFileExists($jarFile);
		$this->assertContains('Cookie File', file_get_contents($jarFile));

		// remove jar file
		unlink($jarFile);
		$this->assertFileNotExists($jarFile);
	}
}