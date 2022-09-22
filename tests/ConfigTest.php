<?php

/**
 * Set of unit tests for Config class
 */

use Nano\Config;

class ConfigTest extends \Nano\NanoBaseTest
{
    public function testGetDirectory()
    {
        $dir = __DIR__;

        $config = new Config($dir);

        $this->assertEquals($dir, $config->getDirectory());
    }

    public function testGetSetDelete()
    {
        $dir = __DIR__;
        $key = 'foo.bar';
        $val = 'test';

        $config = new Config($dir);

        $this->assertNull($config->get($key));
        $this->assertEquals('bar', $config->get($key, 'bar'));

        $config->set($key, $val);

        $this->assertEquals($val, $config->get($key));
        $this->assertEquals($val, $config->get($key, 'bar'));

        $config->delete($key, $val);

        $this->assertNull($config->get($key));
        $this->assertEquals('foo', $config->get($key, 'foo'));

        $config->set($key, [
            'foo' => 'bar',
            'test' => true,
        ]);

        $this->assertEquals([
            'abc' => 123,
            'foo' => 'bar',
            'test' => true,
        ], $config->get($key, [
            'abc' => 123,
            'test' => false,
        ]));
    }
}
