<?php

use Nano\NanoBaseTest;

/**
 * Set of unit tests for DatabaseMysql class
 */

class DatabaseMocked extends DatabaseMysql
{
    public function query(string $sql, ?string $fname = null): DatabaseResult
    {
        $this->lastQuery = $sql;
        return new DatabaseResult($this, []);
    }

    // @see http://www.php.net/manual/en/mysqli.real-escape-string.php
    public function escape($value): string
    {
        return addcslashes($value, "'\"\0");
    }

    public function isConnected(): bool
    {
        return true;
    }

    public static function getInstance(NanoApp $app): self
    {
        return new self($app, [], '');
    }
}


class DatabaseMockedMysqlTest extends NanoBaseTest
{
    private $databaseMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMock = DatabaseMocked::getInstance($this->app);
    }

    // assert that given query matches the recent one
    private function assertQueryEquals($expected)
    {
        $this->assertEquals($expected, $this->databaseMock->getLastQuery());
    }

    /**
     * @deprecated use $this->database property directly
     */
    private function getMysqlDatabaseMock(): DatabaseMysql
    {
        return $this->databaseMock;
    }

    public function testMySqlDatabaseMock()
    {
        $database = $this->getMysqlDatabaseMock();

        // check mock
        $this->assertInstanceOf(DatabaseMysql::class, $database);
        $this->assertTrue($database->isConnected());

        // escape
        $this->assertEquals('foo\\"s', $database->escape('foo"s'));
        $this->assertEquals('foo\\\'s', $database->escape('foo\'s'));

        // test performance data
        $performanceData = $database->getPerformanceData();
        $this->assertEquals(0, $performanceData['queries']);
        $this->assertEquals(0, $performanceData['time']);
    }

    public function testQuery()
    {
        $database = $this->getMysqlDatabaseMock();

        // test queries
        $database->query('SET foo = 1');
        $this->assertQueryEquals('SET foo = 1');

        $database->begin();
        $this->assertQueryEquals('BEGIN /* Database::begin */');

        $database->commit();
        $this->assertQueryEquals('COMMIT /* Database::commit */');
    }

    public function testSelect()
    {
        $database = $this->getMysqlDatabaseMock();

        $database->select('pages', '*');
        $this->assertQueryEquals('SELECT /* Database::select */ * FROM pages');

        $database->select('pages', 'id');
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages');

        $database->select('pages', 'id', ['user' => 42]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages WHERE user="42"');

        $database->select('pages', 'id', ['title' => "foo's"]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages WHERE title="foo\\\'s"');

        $database->select('pages', ['id', 'content'], ['user' => 42]);
        $this->assertQueryEquals('SELECT /* Database::select */ id,content FROM pages WHERE user="42"');

        $database->select(['pages', 'users'], ['pages.id AS id', 'user.name AS author'], ['users.id = pages.author']);
        $this->assertQueryEquals('SELECT /* Database::select */ pages.id AS id,user.name AS author FROM pages,users WHERE users.id = pages.author');

        // options
        $database->select('pages', 'id', [], ['limit' => 5]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LIMIT 5');

        $database->select('pages', 'id', [], ['limit' => 5, 'offset' => 10]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LIMIT 5 OFFSET 10');

        $database->select('pages', 'id', [], ['limit' => 5, 'offset' => 10], __METHOD__);
        $this->assertQueryEquals('SELECT /* DatabaseMockedMysqlTest::testSelect */ id FROM pages LIMIT 5 OFFSET 10');

        // joins
        $database->select('pages', 'id', [], ['joins' => ['foo' => ['LEFT JOIN', 'foo=bar']]]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar');

        $database->select('pages', 'id', [], ['joins' => ['foo' => ['LEFT JOIN', 'foo=bar'], 'tbl' => ['JOIN', 'test = foo']]]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar JOIN tbl ON test = foo');

        $database->select('pages', 'id', [], ['limit' => 5, 'offset' => 10, 'joins' => ['foo' => ['LEFT JOIN', 'foo=bar']]]);
        $this->assertQueryEquals('SELECT /* Database::select */ id FROM pages LEFT JOIN foo ON foo=bar LIMIT 5 OFFSET 10');
    }

    public function testDelete()
    {
        $database = $this->getMysqlDatabaseMock();

        $database->delete('pages');
        $this->assertQueryEquals('DELETE /* Database::delete */ FROM pages');

        $database->delete('pages', ['id' => 2]);
        $this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2"');

        $database->delete('pages', ['id > 5']);
        $this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id > 5');

        $database->delete('pages', ['id' => 2], ['limit' => 1]);
        $this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2" LIMIT 1');

        $database->deleteRow('pages', ['id' => 2]);
        $this->assertQueryEquals('DELETE /* Database::delete */ FROM pages WHERE id="2" LIMIT 1');
    }

    public function testUpdate()
    {
        $database = $this->getMysqlDatabaseMock();

        $database->update('pages', ['foo' => 'bar'], ['id' => 1]);
        $this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar" WHERE id="1"');

        $database->update('pages', ['foo' => 'bar', 'id' => 3], ['id' => 1]);
        $this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar",id="3" WHERE id="1"');

        $database->update('pages', ['foo' => 'bar', 'id' => 3], ['id' => 1], ['limit' => 1]);
        $this->assertQueryEquals('UPDATE /* Database::update */ pages SET foo="bar",id="3" WHERE id="1" LIMIT 1');

        $database->update('pages', ['foo' => 'bar'], ['id' => 1], [], __METHOD__);
        $this->assertQueryEquals('UPDATE /* DatabaseMockedMysqlTest::testUpdate */ pages SET foo="bar" WHERE id="1"');

        $database->update(['pages', 'users'], ['pages.author=users.id'], ['users.id' => 1]);
        $this->assertQueryEquals('UPDATE /* Database::update */ pages,users SET pages.author=users.id WHERE users.id="1"');
    }

    public function testInsert()
    {
        $database = $this->getMysqlDatabaseMock();

        $database->insert('pages', ['foo' => 'bar']);
        $this->assertQueryEquals('INSERT INTO /* Database::insert */ pages (`foo`) VALUES ("bar")');

        $database->insert('pages', ['foo' => 'bar', 'test' => 123]);
        $this->assertQueryEquals('INSERT INTO /* Database::insert */ pages (`foo`,`test`) VALUES ("bar","123")');

        $database->insert('pages', ['foo' => 'b"b', 'test' => 123], [], __METHOD__);
        $this->assertQueryEquals('INSERT INTO /* DatabaseMockedMysqlTest::testInsert */ pages (`foo`,`test`) VALUES ("b\\"b","123")');

        $database->insertRows('pages', [['id' => 1], ['id' => 2]]);
        $this->assertQueryEquals('INSERT INTO /* Database::insertRows */ pages (`id`) VALUES ("1"),("2")');

        $database->insertRows('pages', [['bar' => 1, 'foo' => 123], ['bar' => 2, 'foo' => 456]]);
        $this->assertQueryEquals('INSERT INTO /* Database::insertRows */ pages (`bar`,`foo`) VALUES ("1","123"),("2","456")');

        // @see https://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html
        $database->insert('pages', ['foo' => 'bar', 'test' => 123], ['ON DUPLICATE KEY UPDATE' => ['test', '123']]);
        $this->assertQueryEquals('INSERT INTO /* Database::insert */ pages (`foo`,`test`) VALUES ("bar","123") ON DUPLICATE KEY UPDATE test = "123"');
    }

    public function testReplace()
    {
        $database = $this->getMysqlDatabaseMock();

        $database->replace('pages', ['foo' => 'bar']);
        $this->assertQueryEquals('REPLACE INTO /* Database::replace */ pages (`foo`) VALUES ("bar")');

        $database->replace('pages', ['bar' => 1, 'foo' => 123]);
        $this->assertQueryEquals('REPLACE INTO /* Database::replace */ pages (`bar`,`foo`) VALUES ("1","123")');
    }

    // requires server running on localhost:3306
    public function testMySqlDatabase()
    {
        $this->markTestSkipped('Requires server running on localhost:3306');

        $app = Nano::app(dirname(__FILE__) . '/app');
        $database = Database::connect($app, ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'pass' => '', 'database' => 'test']);

        // test performance data
        $performanceData = $database->getPerformanceData();
        $this->assertEquals(0, $performanceData['queries']);
        $this->assertEquals(0, $performanceData['time']);

        $res = $database->select('test', '*');
        foreach ($res as $i => $row) {
            #var_dump($row);
        }
        $res->free();

        $res = $database->select('test', '*');
        while ($row = $res->fetchRow()) {
            #var_dump($row);
        }
        $res->free();

        $row = $database->selectRow('test', '*', ['id' => 2]);
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
