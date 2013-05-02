<?php

/**
 * Set of unit tests for Template class
 */

class TemplateTest extends PHPUnit_Framework_TestCase {

	private $dir;

	public function setUp() {
		$this->dir = realpath(dirname(__FILE__). '/app/controllers/foo/templates');
	}

	private function getTemplate() {
		return new Template($this->dir);
	}

	public function testTemplateGetPath() {
		$template = $this->getTemplate();
		$this->assertEquals($this->dir . '/test.tmpl.php' , $template->getPath('test'));
		$this->assertEquals($this->dir . '/notExistingTemplate.tmpl.php' , $template->getPath('notExistingTemplate'));
	}

	public function testNotExistingTemplate() {
		$template = $this->getTemplate();
		$this->assertFalse($template->render('notExistingTemplate'));
	}

	public function testTemplateRender() {
		$template = $this->getTemplate();
		$template->set('title', '');
		$this->assertInternalType('string', $template->render('test'));
	}

	public function testTemplateSet() {
		$template = $this->getTemplate();
		$template->set('title', 'The Page');
		$this->assertContains('<h1>The Page</h1>', $template->render('test'));

		$template->set('items', array('foo', 'bar'));
		$this->assertContains('<ul>', $template->render('test'));
		$this->assertContains('<li>foo</li>', $template->render('test'));
		$this->assertContains('<li>bar</li>', $template->render('test'));
	}

	public function testTemplateMultipleSet() {
		$template = $this->getTemplate();
		$template->set(array(
			'title' => 'The Page',
			'items' => array('foo', 'bar'),
		));

		$this->assertContains('<h1>The Page</h1>', $template->render('test'));
		$this->assertContains('<ul>', $template->render('test'));
		$this->assertContains('<li>foo</li>', $template->render('test'));
		$this->assertContains('<li>bar</li>', $template->render('test'));

		// change previously set variable
		$template->set(array('title' => 'Foo&bar'));
		$this->assertContains('<h1>Foo&amp;bar</h1>', $template->render('test'));
		$this->assertContains('<li>foo</li>', $template->render('test'));
	}
}