<?php

/**
 * Set of unit tests for Nano class
 */

class NanoTest extends \Nano\NanoBaseTest {

	public function testDirectories() {
		$dir = realpath(dirname(__FILE__) . '/..');

		$this->assertEquals($dir, Nano::getCoreDirectory());
		$this->assertEquals($dir . '/lib', Nano::getLibDirectory());
	}

	public function testAddLibrary() {
		$libName = 'testLib';

		Nano::addLibrary($libName);

		$this->assertStringContainsString($libName, get_include_path());
	}
}