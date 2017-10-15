<?php

use Nano\NanoDatabaseMock;
use Nano\NanoBaseTest;

/**
 * Set of unit tests for DatabaseResult class
 */
class DatabaseResultTest extends NanoBaseTest {

	/* @var NanoDatabaseMock $dbMock */
	private $dbMock;

	/* @var DatabaseResult $results */
	private $results;

	function setUp() {
		parent::setUp();
		$this->dbMock = new NanoDatabaseMock($this->app);

		$this->dbMock->setResult([
			['foo' => 1],
			['foo' => 42],
			['foo' => 35],
		]);

		$this->results = new DatabaseResult($this->dbMock, []);
	}

	function testNumRowsAndCount() {
		$this->assertEquals(3, $this->results->numRows());
		$this->assertEquals(3, $this->results->count());
	}

	function testFetchRow() {
		$this->assertEquals(['foo' => 1], $this->results->fetchRow());
	}

	function testFetchRows() {
		$this->assertEquals(['foo' => 1], $this->results->fetchRow());
		$this->assertEquals(['foo' => 42], $this->results->fetchRow());
		$this->assertEquals(['foo' => 35], $this->results->fetchRow());
		$this->assertFalse($this->results->fetchRow()); // no more results
	}

	function testFetchField() {
		$this->assertEquals(1, $this->results->fetchField());
	}

	function testFetchFields() {
		$this->assertEquals(1, $this->results->fetchField());
		$this->assertEquals(42, $this->results->fetchField());
		$this->assertEquals(35, $this->results->fetchField());
		$this->assertFalse($this->results->fetchField());
	}

	function testIteratorAndGetKey() {
		$this->assertEquals(0, $this->results->key());

		$rows = [];
		foreach($this->results as $row) {
			$rows[] = $row;
		}

		$this->assertEquals(3, count($rows));

		$this->assertEquals(1, $rows[0]['foo']);
		$this->assertEquals(42, $rows[1]['foo']);
		$this->assertEquals(35, $rows[2]['foo']);

		$this->assertEquals(4, $this->results->key());
		$this->assertFalse($this->results->current());

		// free the results
		$this->results->free();
	}

}
