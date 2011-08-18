<?php

/**
 * Set of unit tests for Output class
 *
 * $Id$
 */

class OutputTest extends PHPUnit_Framework_TestCase {

	private $data = array(
		'foo' => 'bar',
		'test' => array(
			'123',
			'456'
		)
	);

	public function testOutputFactory() {
		$output = Output::factory('json');
		$this->assertInstanceOf('OutputJson', $output);
		$this->assertNull($output->getData());

		$output = Output::factory('JSON', array('foo'));
		$this->assertInstanceOf('OutputJson', $output);

		$output = Output::factory('Unknown');
		$this->assertNull($output);
	}

	public function testOutputJSON() {
		$output = Output::factory('json', $this->data);
		$this->assertEquals('{"foo":"bar","test":["123","456"]}', $output->render());
		$this->assertEquals('application/json; charset=UTF-8', $output->getContentType());
		$this->assertEquals($this->data, $output->getData());

		$output->setData(array('123', '456'));
		$this->assertEquals('["123","456"]', $output->render());
	}

	public function testOutputXML() {
		$output = Output::factory('xml', $this->data);
		$this->assertEquals("<?xml version=\"1.0\"?>\n<root><foo>bar</foo><test><value>123</value><value>456</value></test></root>", $output->render());
		$this->assertEquals('text/xml; charset=UTF-8', $output->getContentType());
		$this->assertEquals($this->data, $output->getData());
	}

	public function testOutputJSONP() {
		$output = Output::factory('jsonp', $this->data);
		$this->assertEquals('callback({"foo":"bar","test":["123","456"]})', $output->render());
		$this->assertEquals('application/javascript; charset=UTF-8', $output->getContentType());
		$this->assertEquals($this->data, $output->getData());

		// custom callback
		$callback = 'f' . mt_rand(0,100);
		$output->setCallback($callback);
		$this->assertEquals($callback . '({"foo":"bar","test":["123","456"]})', $output->render());
		$this->assertEquals($this->data, $output->getData());
	}

	public function testOutputTemplate() {
		$dir = dirname(__FILE__). '/app/modules/foo/templates';
		$template = new Template($dir);
		$template->set(array('id' => 'foo'));

		$output = Output::factory('template');
		$output->setTemplate($template);
		$output->setTemplateName('bar');

		$this->assertEquals('<h1>foo</h1>', $output->render());
		$this->assertEquals('text/html; charset=UTF-8', $output->getContentType());
		$this->assertNull($output->getData());

		// pass template's data to the Output object
		$template = new Template($dir);

		$output = Output::factory('template', array('id' => 'bar'));
		$output->setTemplate($template);
		$output->setTemplateName('bar');

		$this->assertEquals('<h1>bar</h1>', $output->render());
		$this->assertEquals(array('id' => 'bar'), $output->getData());

		// template not provided
		$output = Output::factory('template');
		$this->assertFalse($output->render());
	}
}