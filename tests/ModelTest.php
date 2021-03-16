<?php

/**
 * Set of unit tests for Model class
 */
class ModelTest extends \Nano\NanoBaseTest {

	public function setUp(): void {
		$dir = realpath(dirname(__FILE__) . '/app');
		$this->app = Nano::app($dir);
	}

	public function testGetMagic() {
		$model = $this->app->factory('TestModel');

		$this->assertEquals($model->getData(), array('foo' => 'bar'));
		$this->assertEquals($model->getFoo(), 'bar');
	}
}
