<?php

/**
 * Set of unit tests for Nano class
 *
 * $Id$
 */

class NanoTest extends PHPUnit_Framework_TestCase {

	public function testDirectories() {
		$dir = realpath(dirname(__FILE__) . '/..');

		$this->assertEquals($dir, Nano::getCoreDirectory());
		$this->assertEquals($dir . '/lib', Nano::getLibDirectory());
	}

	public function testAddLibrary() {
		$libName = 'testLib';

		Nano::addLibrary($libName);

		$this->assertContains($libName, get_include_path());
	}
}