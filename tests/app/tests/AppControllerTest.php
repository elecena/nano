<?php

/**
 * Set of unit tests for Nano's Application controller
 */

include_once(dirname(__FILE__) . '/AppTest.php');

class AppControllerTest extends AppTest {
	public function testControllers() {
		$obj = $this->app->getController('Foo');

		$this->assertInstanceOf('FooController', $obj);
		$this->assertNull($obj->bar('123'));
		$this->assertEquals(array('id' => 123), $obj->getData());

		// test creation of not existing controller
		$this->assertNull($this->app->getController('NotExistingController'));
		$this->assertNull(Controller::factory($this->app, 'NotExistingController'));

		// normalize controllers names
		$this->assertInstanceOf('FooController', $this->app->getController('foo'));
		$this->assertInstanceOf('FooController', $this->app->getController('FOO'));
		$this->assertInstanceOf('FooController', $this->app->getController('FoO'));

		// output's format
		$controller = $this->app->getController('Foo');

		$view = new View($this->app, $controller);
		$controller->setView($view);

		$this->assertNull($controller->getFormat());

		$controller->setFormat('json');
		$this->assertEquals('json', $controller->getFormat());
	}

	public function testControllersVariables() {
		$controller = $this->app->getController('Foo');

		$this->assertTrue(empty($controller->foo));

		$controller->foo = 'foo';
		$this->assertFalse(empty($controller->foo));
		$this->assertEquals($controller->foo, 'foo');

		// unset
		unset($controller->foo);
		$this->assertTrue(empty($controller->foo));
	}

	public function testControllersView() {
		$controller = $this->app->getController('Foo');
		$view = new View($this->app, $controller);
		$controller->setView($view);

		// render HTML
		$controller->id = 123;
		$this->assertEquals('<h1>123</h1>', $controller->render('bar'));

		$controller->id = 'test';
		$this->assertEquals('<h1>test</h1>', $controller->render('bar'));
	}

	public function testControllerEvents() {
		$events = $this->app->getEvents();
		$controller = $this->app->getController('Foo');

		$value = 'foo';

		$this->assertTrue($events->fire('eventFoo', array(&$value)));
		$this->assertEquals('footest', $value);

		// events firing
		$this->assertEquals('footest', $controller->event('foo'));
	}
}