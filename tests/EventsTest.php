<?php

/**
 * Set of unit tests for Events class
 *
 * $Id$
 */

class EventsTest extends PHPUnit_Framework_TestCase {

	public function testOnFire() {
		$events = new Events();
		$events->bind('event', array($this, 'handlerTrue'));

		$this->assertTrue($events->fire('event'));
		$this->assertTrue($events->fire('eventfoo'));
	}

	public function testOnFireStop() {
		$events = new Events();
		$events->bind('event', array($this, 'handlerFalse'));

		$this->assertFalse($events->fire('event'));
	}

	public function testOnFirePass() {
		$value = '0';

		$events = new Events();
		$events->bind('event', array($this, 'handlerTrue'));

		$this->assertTrue($events->fire('event', array(&$value)));
		$this->assertEquals('0123', $value);
	}

	public function testOnFirePassQueue() {
		$value = '0';

		$events = new Events();
		$events->bind('event', array($this, 'handlerTrue'));
		$events->bind('event', array($this, 'handlerFalse'));

		$this->assertFalse($events->fire('event', array(&$value)));
		$this->assertEquals('0123456', $value);
	}

	public function testOnFirePassStop() {
		$value = '0';

		$events = new Events();
		$events->bind('event', array($this, 'handlerFalse'));
		$events->bind('event', array($this, 'handlerTrue'));

		$this->assertFalse($events->fire('event', array(&$value)));
		$this->assertEquals('0456', $value);
	}

	public function handlerTrue($foo = '') {
		$foo .= '123';
		return true;
	}

	public function handlerFalse($foo = '') {
		$foo .= '456';
		return false;
	}
}