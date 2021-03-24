<?php

use Nano\NanoBaseTest;
use Nano\TestApp\TestModel;

/**
 * Set of unit tests for Model class
 *
 * @covers TestModel
 */
class ModelTest extends NanoBaseTest
{
    /**
     * @var TestModel
     */
    private $model;

    public function setUp(): void
    {
        parent::setUp();
        $this->model = new TestModel();
    }

    public function testGetMagic()
    {
        $this->assertEquals(['foo' => 'bar'], $this->model->getData());
        $this->assertEquals('bar', $this->model->getFoo());
    }
}
