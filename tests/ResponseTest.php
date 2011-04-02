<?php

/**
 * Set of unit tests for Response class
 *
 * $Id$
 */

class ResponseTest extends PHPUnit_Framework_TestCase {

	public function testTextResponse() {
		$text = "foo\nbar";

		$response = new Response();
		$response->setContent($text);

		$this->assertEquals($text, $response->getContent());
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