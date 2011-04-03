<?php

/**
 * Set of unit tests for Output class
 *
 * $Id$
 */

class OutputTest extends PHPUnit_Framework_TestCase {

	public function testOutputFactory() {
		$output = Output::factory('json');
		$this->assertInstanceOf('OutputJson', $output);

		$output = Output::factory('JSON', array('foo'));
		$this->assertInstanceOf('OutputJson', $output);

		$output = Output::factory('Unknown');
		$this->assertNull($output);
	}

	public function testOutputRender() {
		$data = array(
			'foo' => 'bar',
			'test' => array(
				'123',
				'456'
			),
		);

		// JSON
		$output = Output::factory('json', $data);
		$this->assertEquals('{"foo":"bar","test":["123","456"]}', $output->render());
		$this->assertEquals('application/json', $output->getContentType());

		$output->setData(array('123', '456'));
		$this->assertEquals('["123","456"]', $output->render());

		// XML
		$output = Output::factory('xml', $data);
		$this->assertEquals("<?xml version=\"1.0\"?>\n<root><foo>bar</foo><test><value>123</value><value>456</value></test></root>", $output->render());
		$this->assertEquals('text/xml', $output->getContentType());

		// JSONP (JSON + callback)
		$output = Output::factory('jsonp', $data);
		$this->assertEquals('callback({"foo":"bar","test":["123","456"]})', $output->render());
		$this->assertEquals('application/javascript', $output->getContentType());

		// custom callback
		$callback = 'f' . mt_rand(0,100);
		$output->setCallback($callback);
		$this->assertEquals($callback . '({"foo":"bar","test":["123","456"]})', $output->render());
	}
}