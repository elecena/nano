<?php

/**
 * Set of unit tests for Output class
 */

use Nano\Output;
use Nano\Template;

class OutputTest extends \Nano\NanoBaseTest
{
    private $data = [
        'foo' => 'bar',
        'test' => [
            '123',
            '456'
        ]
    ];

    public function testOutputFactory()
    {
        $output = Output::factory('json');
        $this->assertInstanceOf('Nano\Output\OutputJson', $output);
        $this->assertNull($output->getData());

        $output = Output::factory('JSON', ['foo']);
        $this->assertInstanceOf('Nano\Output\OutputJson', $output);
    }

    public function testOutputJSON()
    {
        $output = Output::factory('json', $this->data);
        $this->assertEquals('{"foo":"bar","test":["123","456"]}', $output->render());
        $this->assertEquals('application/json; charset=UTF-8', $output->getContentType());
        $this->assertEquals($this->data, $output->getData());

        $output->setData(['123', '456']);
        $this->assertEquals('["123","456"]', $output->render());
    }

    public function testOutputXML()
    {
        $output = Output::factory('xml', $this->data);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><foo>bar</foo><test><value>123</value><value>456</value></test></root>", $output->render());
        $this->assertEquals('text/xml; charset=UTF-8', $output->getContentType());
        $this->assertEquals($this->data, $output->getData());
    }

    public function testOutputComplexXML()
    {
        $output = Output::factory('xml', [
            ['FOO' => 'bar'],
            ['Foo' => 'test'],
        ]);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><item><foo>bar</foo></item><item><foo>test</foo></item></root>", $output->render());
        $this->assertEquals('text/xml; charset=UTF-8', $output->getContentType());
    }

    public function testOutputJSONP()
    {
        /* @var Nano\Output\OutputJsonp $output */
        $output = Output::factory('jsonp', $this->data);
        $this->assertEquals('callback({"foo":"bar","test":["123","456"]})', $output->render());
        $this->assertEquals('application/javascript; charset=UTF-8', $output->getContentType());
        $this->assertEquals($this->data, $output->getData());

        // custom callback
        $callback = 'f' . mt_rand(0, 100);
        $output->setCallback($callback);
        $this->assertEquals($callback . '({"foo":"bar","test":["123","456"]})', $output->render());
        $this->assertEquals($this->data, $output->getData());
    }

    public function testOutputTemplate()
    {
        $dir = dirname(__FILE__). '/app/controllers/foo/templates';
        $template = new Template($dir);
        $template->set(['id' => 'foo']);

        /* @var Nano\Output\OutputTemplate $output */
        $output = Output::factory('template');
        $output->setTemplate($template);
        $output->setTemplateName('bar');

        $this->assertEquals('<h1>foo</h1>', $output->render());
        $this->assertEquals('text/html; charset=UTF-8', $output->getContentType());
        $this->assertNull($output->getData());

        // pass template's data to the Output object
        $template = new Template($dir);

        $output = Output::factory('template', ['id' => 'bar']);
        $output->setTemplate($template);
        $output->setTemplateName('bar');

        $this->assertEquals('<h1>bar</h1>', $output->render());
        $this->assertEquals(['id' => 'bar'], $output->getData());

        // template not provided
        $output = Output::factory('template');
        $this->assertFalse($output->render());
    }
}
