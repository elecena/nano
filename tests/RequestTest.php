<?php

/**
 * Set of unit tests for Request class
 *
 * $Id$
 */

class RequestTest extends PHPUnit_Framework_TestCase {

	public function testGETParams() {
		// emulate GET request
		$request = new Request(array(
			'foo' => 'bar',
			'test' => '2',
			'box' => 'on',
		),
		array(
			'REQUEST_METHOD' => 'GET'
		));

		$this->assertFalse($request->wasPosted());

		$this->assertNull($request->get('foo2'));
		$this->assertEquals('bar', $request->get('foo'));
		$this->assertEquals(0, $request->getInt('foo'));
		$this->assertEquals(2, $request->getInt('test'));
		$this->assertTrue($request->getChecked('box'));
		$this->assertFalse($request->getChecked('box2'));
	}

	public function testPOSTParams() {
		// emulate POST request
		$request = new Request(array(
			'foo' => 'bar',
			'test' => '2',
			'box' => 'on',
		),
		array(
			'REQUEST_METHOD' => 'POST'
		));

		$this->assertTrue($request->wasPosted());

		$this->assertNull($request->get('test2'));
		$this->assertEquals('bar', $request->get('foo'));
		$this->assertEquals(0, $request->getInt('foo'));
		$this->assertEquals(2, $request->getInt('test'));
		$this->assertTrue($request->getChecked('box'));
		$this->assertFalse($request->getChecked('box2'));
	}

	public function testIP() {
		// crawl-66-249-66-248.googlebot.com
		$ip = '66.249.66.248';

		$request = new Request(array(), array('HTTP_CLIENT_IP' => $ip));
		$this->assertEquals($ip, $request->getIP());

		$request = new Request(array(), array('REMOTE_ADDR' => $ip));
		$this->assertEquals($ip, $request->getIP());

		$request = new Request(array(), array('HTTP_X_FORWARDED_FOR' => $ip));
		$this->assertEquals($ip, $request->getIP());

		$request = new Request(array(), array('HTTP_X_FORWARDED_FOR' => "192.168.0.1, {$ip}"));
		$this->assertEquals($ip, $request->getIP());
	}
}