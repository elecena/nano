<?php

/**
 * Set of unit tests for Nano's Application controller
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/AppTest.php');

class AppControllerTest extends AppTest {

	public function testControllers() {
		$this->assertEquals(array('Foo', 'Static'), $this->app->getModules());

		$obj = $this->app->getModule('Foo');

		$this->assertInstanceOf('FooModule', $obj);
		$this->assertNull($obj->bar('123'));
		$this->assertEquals(array('id' => 123), $obj->getData());

		// test creation of not existing module
		$this->assertNull($this->app->getModule('NotExistingModule'));
		$this->assertNull(Module::factory($this->app, 'NotExistingModule'));

		// normalize modules names
		$this->assertInstanceOf('FooModule', $this->app->getModule('foo'));
		$this->assertInstanceOf('FooModule', $this->app->getModule('FOO'));
		$this->assertInstanceOf('FooModule', $this->app->getModule('FoO'));

		// output's format
		$module = $this->app->getModule('Foo');

		$this->assertNull($module->getFormat());

		$module->setFormat('json');
		$this->assertEquals('json', $module->getFormat());

		// render HTML
		$module->id = 123;
		$this->assertEquals('<h1>123</h1>', $module->render('bar'));

		$module->id = 'test';
		$this->assertEquals('<h1>test</h1>', $module->render('bar'));
	}

	public function testControllerEvents() {
		$events = $this->app->getEvents();
		$module = $this->app->getModule('Foo');

		$value = 'foo';

		$this->assertTrue($events->fire('eventFoo', array(&$value)));
		$this->assertEquals('footest', $value);

		// events firing
		$this->assertEquals('footest', $module->event('foo'));
	}
}