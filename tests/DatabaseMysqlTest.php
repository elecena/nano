<?php

/**
 * Set of unit tests for DatabaseMysql class
 *
 * $Id$
 */

class DatabaseMysqlTest extends PHPUnit_Framework_TestCase {

	private $lastQuery;

	// assert that given query matches the recent one
	private function assertQueryEquals($expected) {
		$this->assertEquals($expected, $this->lastQuery);
	}

	public function queryMock($query) {
		$this->lastQuery = $query;
		return false;
	}

	// @see http://www.php.net/manual/en/mysqli.real-escape-string.php
	public function escapeMock($value) {
		return addcslashes($value, "'\"\0");
	}

	private function getDatabaseMock() {

		// set up DatabaseMysql class, so that mockup will work
		if (!class_exists('DatabaseMysql')) {
			// load MySQL driver
			$app = Nano::app(dirname(__FILE__) . '/app');
			$database = Database::connect($app, array('driver' => 'mysql'));
		}

		// mock the database driver
		$database = $this->getMockBuilder('DatabaseMysql')
			->disableOriginalConstructor()
			->setMethods(array('query', 'escape', 'isConnected'))
			->setMockClassName('DatabaseMysqlMock' . mt_rand())
			->getMock();

		// mock certain methods
		$database->expects($this->any())
			->method('query')
			->will($this->returnCallback(array($this, 'queryMock')));

		$database->expects($this->any())
			->method('escape')
			->will($this->returnCallback(array($this, 'escapeMock')));

		$database->expects($this->any())
			->method('isConnected')
			->will($this->returnValue(true));

		return $database;
	}

	public function testMySqlDatabaseMock() {
		$database = $this->getDatabaseMock();

		// check mock
		$this->assertInstanceOf('DatabaseMysql', $database);
		$this->assertTrue($database->isConnected());

		// escape
		$this->assertEquals('foo\\"s', $database->escape('foo"s'));
		$this->assertEquals('foo\\\'s', $database->escape('foo\'s'));

		// test performance data
		$performanceData = $database->getPerformanceData();
		$this->assertEquals(0, $performanceData['queries']);
		$this->assertEquals(0, $performanceData['time']);
	}

	public function testQuery() {
		$database = $this->getDatabaseMock();

		// test queries
		$database->query('SET foo = 1');
		$this->assertQueryEquals('SET foo = 1');

		$database->begin();
		$this->assertQueryEquals('BEGIN /* Database::begin */');

		$database->commit();
		$this->assertQueryEquals('COMMIT /* Database::commit */');
	}

	public function testSelect() {
		$database = $this->getDatabaseMock();

		$database->select('pages', '*');
		$this->assertQueryEquals('SELECT /* Database::select */ * FROM pages');

		$database->select('pages', 'id');
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages');

		$database->select('pages', 'id', array('user' => 42));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages WHERE user="42"');

		$database->select('pages', 'id', array('title' => "foo's"));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages WHERE title="foo\\\'s"');

		$database->select('pages', array('id', 'content'), array('user' => 42));
		$this->assertQueryEquals('SELECT /* Database::select */ id,content FROM pages WHERE user="42"');

		$database->select(array('pages', 'users'), array('pages.id AS id', 'user.name AS author'), array('users.id = pages.author'));
		$this->assertQueryEquals('SELECT /* Database::select */ pages.id AS id,user.name AS author FROM pages,users WHERE users.id = pages.author');

		// options
		$database->select('pages', 'id', array(), array('limit' => 5));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LIMIT 5');

		$database->select('pages', 'id', array(), array('limit' => 5, 'offset' => 10));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LIMIT 5 OFFSET 10');

		$database->select('pages', 'id', array(), array('limit' => 5, 'offset' => 10), __METHOD__);
		$this->assertQueryEquals('SELECT /* DatabaseMysqlTest::testSelect */ id FROM pages LIMIT 5 OFFSET 10');

		// joins
		$database->select('pages', 'id', array(), array('joins' => array('foo' => array('LEFT JOIN', 'foo=bar'))));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar');

		$database->select('pages', 'id', array(), array('joins' => array('foo' => array('LEFT JOIN', 'foo=bar'), 'tbl' => array('JOIN', 'test = foo'))));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar JOIN tbl ON test = foo');

		$database->select('pages', 'id', array(), array('limit' => 5, 'offset' => 10, 'joins' => array('foo' => array('LEFT JOIN', 'foo=bar'))));
		$this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar LIMIT 5 OFFSET 10');
	}

	public function testDelete() {
		$database = $this->getDatabaseMock();

		$database->delete('pages');
		$this->assertQueryEquals('DELETE /* Database::delete */ FROM pages');

		$database->delete('pages', array('id' => 2));
		$this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2"');

		$database->delete('pages', array('id > 5'));
		$this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id > 5');

		$database->delete('pages', array('id' => 2), array('limit' => 1));
		$this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2" LIMIT 1');

		$database->deleteRow('pages', array('id' => 2));
		$this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2" LIMIT 1');
	}

	public function testUpdate() {
		$database = $this->getDatabaseMock();

		$database->update('pages', array('foo' => 'bar'), array('id' => 1));
		$this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar" WHERE id="1"');

		$database->update('pages', array('foo' => 'bar', 'id' => 3), array('id' => 1));
		$this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar",id="3" WHERE id="1"');

		$database->update('pages', array('foo' => 'bar', 'id' => 3), array('id' => 1), array('limit' => 1));
		$this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar",id="3" WHERE id="1" LIMIT 1');

		$database->update('pages', array('foo' => 'bar'), array('id' => 1), array(), __METHOD__);
		$this->assertQueryEquals('UPDATE /* DatabaseMysqlTest::testUpdate */ pages SET foo="bar" WHERE id="1"');

		$database->update(array('pages', 'users'), array('pages.author=users.id'), array('users.id' => 1));
		$this->assertQueryEquals('UPDATE /* Database::update */ pages,users SET pages.author=users.id WHERE users.id="1"');
	}

	public function testInsert() {
		$database = $this->getDatabaseMock();

		$database->insert('pages', array('foo' => 'bar'));
		$this->assertQueryEquals('INSERT INTO /* Database::insert */ pages (`foo`) VALUES ("bar")');

		$database->insert('pages', array('foo' => 'bar', 'test' => 123));
		$this->assertQueryEquals('INSERT INTO /* Database::insert */ pages (`foo`,`test`) VALUES ("bar","123")');

		$database->insert('pages', array('foo' => 'b"b', 'test' => 123), array(), __METHOD__);
		$this->assertQueryEquals('INSERT INTO /* DatabaseMysqlTest::testInsert */ pages (`foo`,`test`) VALUES ("b\\"b","123")');

		$database->insertRows('pages', array(array('id' => 1), array('id' => 2)));
		$this->assertQueryEquals('INSERT INTO /* Database::insertRows */ pages (`id`) VALUES ("1"),("2")');

		$database->insertRows('pages', array(array('bar' => 1, 'foo' => 123), array('bar' => 2, 'foo' => 456)));
		$this->assertQueryEquals('INSERT INTO /* Database::insertRows */ pages (`bar`,`foo`) VALUES ("1","123"),("2","456")');
	}

	// requires server running on localhost:3306
	public function testMySqlDatabase() {
		return;

		$app = Nano::app(dirname(__FILE__) . '/app');
		$database = Database::connect($app, array('driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'pass' => '', 'database' => 'test'));

		// test performance data
		$performanceData = $database->getPerformanceData();
		$this->assertEquals(0, $performanceData['queries']);
		$this->assertEquals(0, $performanceData['time']);

		$res = $database->select('test', '*');
		foreach($res as $i => $row) {
			#var_dump($row);
		}
		$res->free();

		$res = $database->select('test', '*');
		while($row = $res->fetchRow()) {
			#var_dump($row);
		}
		$res->free();

		$row = $database->selectRow('test', '*', array('id' => 2));
		#var_dump($row);

		$row = $database->selectField('test', 'count(*)');
		#var_dump($row);

		$res = $database->query('SELECT VERSION()');
		#var_dump($res->fetchField());

		$performanceData = $database->getPerformanceData();
		$this->assertEquals(5, $performanceData['queries']);
		$this->assertTrue($performanceData['time'] > 0);
	}
}