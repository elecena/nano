<?php

/**
 * Set of unit tests for Template class
 */

use Nano\Template;

class TemplateTest extends \Nano\NanoBaseTest
{
    private $dir;

    public function setUp(): void
    {
        $this->dir = realpath(__DIR__. '/app/controllers/foo/templates');
    }

    private function getTemplate()
    {
        return new Template($this->dir);
    }

    public function testTemplateGetPath()
    {
        $template = $this->getTemplate();
        $this->assertEquals($this->dir . '/test.tmpl.php', $template->getPath('test'));
        $this->assertEquals($this->dir . '/notExistingTemplate.tmpl.php', $template->getPath('notExistingTemplate'));
    }

    public function testNotExistingTemplate()
    {
        $template = $this->getTemplate();
        $this->assertFalse($template->render('notExistingTemplate'));
    }

    public function testTemplateRender()
    {
        $template = $this->getTemplate();
        $template->set('title', '');
        $this->assertTrue(is_string($template->render('test')));
    }

    public function testTemplateSet()
    {
        $template = $this->getTemplate();
        $template->set('title', 'The Page');
        $this->assertStringContainsString('<h1>The Page</h1>', $template->render('test'));

        $template->set('items', ['foo', 'bar']);
        $this->assertStringContainsString('<ul>', $template->render('test'));
        $this->assertStringContainsString('<li>foo</li>', $template->render('test'));
        $this->assertStringContainsString('<li>bar</li>', $template->render('test'));
    }

    public function testTemplateMultipleSet()
    {
        $template = $this->getTemplate();
        $template->set([
            'title' => 'The Page',
            'items' => ['foo', 'bar'],
        ]);

        $this->assertStringContainsString('<h1>The Page</h1>', $template->render('test'));
        $this->assertStringContainsString('<ul>', $template->render('test'));
        $this->assertStringContainsString('<li>foo</li>', $template->render('test'));
        $this->assertStringContainsString('<li>bar</li>', $template->render('test'));

        // change previously set variable
        $template->set(['title' => 'Foo&bar']);
        $this->assertStringContainsString('<h1>Foo&amp;bar</h1>', $template->render('test'));
        $this->assertStringContainsString('<li>foo</li>', $template->render('test'));
    }
}
