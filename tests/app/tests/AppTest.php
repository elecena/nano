<?php

/**
 * Set of unit tests for Nano's Application class
 *
 * $Id$
 */

class AppTest extends PHPUnit_Framework_TestCase {

	private $app;
	private $config;
	private $dir;

	public function setUp() {
		$this->dir = realpath(dirname(__FILE__) . '/..');
		$this->config = array();

		$this->app = Nano::app($this->dir, $this->config);
	}

	public function testCreateApp() {
		$this->assertInstanceOf('NanoApp', $this->app);
		$this->assertEquals($this->dir, $this->app->getDirectory());
	}
}