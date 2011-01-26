<?php

/**
 * Set of unit tests for Nano class
 *
 * $Id$
 */

class NanoTest extends PHPUnit_Framework_TestCase {

	// instance of framework
	private $nano;

	public function setUp() {
		$this->nano = Nano::init();
	}

	public function testDirectories() {
		$dir = realpath(dirname(__FILE__) . '/..');

		$this->assertEquals($dir, $this->nano->getCoreDirectory());
		$this->assertEquals($dir . '/lib', $this->nano->getLibDirectory());
	}
}