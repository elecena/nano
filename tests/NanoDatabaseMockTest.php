<?php

use Nano\NanoBaseTest;
use Nano\NanoDatabaseMock;

/**
 * Unit tests for NanoDatabaseMock class
 */
class NanoDatabaseMockTest extends NanoBaseTest {

	/* @var NanoDatabaseMock $mock */
	private $mock;

	function setUp() {
		parent::setUp();
		$this->mock = new NanoDatabaseMock($this->app);
	}

	/**
	 * @param string $value
	 * @param string $expected
	 * @dataProvider escapeDataProvider
	 */
	function testEscape($value, $expected) {
		$this->assertEquals($expected, $this->mock->escape($value));
	}

	/**
	 * @return array
	 */
	function escapeDataProvider() {
		return [
			['foo', 'foo'],
			['f"oo', 'f\"oo'],
			['f\'oo', 'f\\\'oo'],
		];
	}

	function testSetResultRow() {
		$this->mock->setResultRow(['foo' => 42]);
		$res = $this->mock->query('SELECT foo FROM bar', __METHOD__);

		$this->assertEquals(1, $res->count());
		$this->assertEquals(['foo' => 42], $res->fetchRow());
	}

	function testSetOnQueryCallback() {
		$called = false;
		$fname = __METHOD__;

		$this->mock->setOnQueryCallback(function($query, $func) use (&$called, $fname) {
			$this->assertEquals('SELECT foo FROM bar', $query);
			$this->assertEquals($fname, $func);
			$called = true;
		});

		$this->mock->query('SELECT foo FROM bar', $fname);

		$this->assertTrue($called, 'setOnQueryCallback() should be called when handling mocked query');
	}

}
