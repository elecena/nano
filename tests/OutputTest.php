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

		$output = Output::factory('json', $data);
		$this->assertEquals('{"foo":"bar","test":["123","456"]}', $output->render());

		$output = Output::factory('json');
		$output->setData($data);
		$this->assertEquals('{"foo":"bar","test":["123","456"]}', $output->render());
	}
}