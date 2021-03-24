<?php

use Nano\NanoBaseTest;

/**
 * Set of unit tests for Model class
 *
 * @covers TestModel
 */
class ModelTest extends NanoBaseTest
{
    public function setUp(): void
    {
        $dir = __DIR__ . '/app';
        $this->app = Nano::app($dir);
    }

    public function testGetMagic()
    {
        $model = new TestModel();

        $this->assertEquals($model->getData(), ['foo' => 'bar']);
        $this->assertEquals($model->getFoo(), 'bar');
    }
}
