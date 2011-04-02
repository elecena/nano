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
}