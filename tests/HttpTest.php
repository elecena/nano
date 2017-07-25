<?php

/**
 * Set of unit tests for Http class
 */

class HttpTest extends \Nano\NanoBaseTest {

	public function testGet() { return;
		// make request to example.org (redirects to http://www.iana.org/domains/example/)
		$resp = Http::get('http://example.org');

		$this->assertEquals(200, $resp->getResponseCode());
		$this->assertEquals('http://www.iana.org/domains/example/', $resp->getLocation());
		$this->assertEquals('http://www.iana.org/domains/example/', $resp->getHeader('Location'));
		$this->assertContains('Example Domains', $resp->getContent());
		$this->assertContains('Example Domains', (string) $resp);
	}

	public function testPost() { return;
		// POST request (ends with HTTP 400)
		$resp = Http::post('http://google.com/images/foo', array('foo' => 'bar')); //var_dump($resp);

		$this->assertEquals(404, $resp->getResponseCode());
		$this->assertContains('text/html', $resp->getHeader('Content-Type'));
		$this->assertContains('Not Found', $resp->getContent());
	}

	public function testHead() { return;
		// HEAD request for not existing image
		$resp = Http::head('http://www.google.pl/images/srpr/nav_logo.png', array('foo' => 'bar')); //var_dump($resp);

		$this->assertEquals(404, $resp->getResponseCode());
		$this->assertEquals('', $resp->getContent());
	}
}