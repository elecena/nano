<?php

use Nano\NanoBaseTest;
use Nano\TestApp\TestModel;

/**
 * Set of unit tests for Model class
 */
class ModelTest extends NanoBaseTest
{
    private TestModel $model;

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
