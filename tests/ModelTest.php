<?php

/**
 * Set of unit tests for Model class
 *
 * $Id$
 */

class TestModel extends Model {

	public function __construct(NanoApp $app) {
		parent::__construct($app);

		$this->data = array(
			'foo' => 'bar',
		);
	}
}

class ModelTest extends PHPUnit_Framework_TestCase {

	private $app;

	public function setUp() {
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);
	}

	public function testGetMagic() {
		$model = $this->app->factory('TestModel');

		$this->assertEquals($model->getData(), array('foo' => 'bar'));
		$this->assertEquals($model->getFoo(), 'bar');
	}
}