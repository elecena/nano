<?php

/**
 * Set of unit tests for Template class
 *
 * $Id$
 */

class TemplateTest extends PHPUnit_Framework_TestCase {

	private function getTemplate() {
		$dir = dirname(__FILE__). '/app/controllers/foo/templates';

		return new Template($dir);
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