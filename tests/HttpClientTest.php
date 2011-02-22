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

	public function testGet() {
		return;
	
		// make request to Wikipedia
		// redirects to http://en.wikipedia.org/wiki/Main_Page
		$client = new HttpClient();
		$resp = $client->get('http://en.wikipedia.org'); //var_dump($client);

		$this->assertRegExp('#<title>Wikipedia, the free encyclopedia</title>#', $resp);


		// make request to Google (which blocks requests done by bots)
		$client = new HttpClient();
		$resp = $client->get('http://www.google.com/search', array('q' => 'foo')); //var_dump($client); //var_dump($resp);

		//$this->assertFalse($resp);
	}
}