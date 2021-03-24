<?php

use Nano\NanoBaseTest;

class DatabaseMysqlTest extends NanoBaseTest
{
    /**
     * @var DatabaseMysql|null $database
     */
    private $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = Database::connect($this->app, ['driver' => 'mysql', 'host' => '0.0.0.0', 'user' => 'root', 'pass' => '', 'database' => 'test']);
    }

    public function testLazyConnect()
    {
        $this->assertInstanceOf(DatabaseMysql::class, $this->database);
        $this->assertFalse($this->database->isConnected(), 'We should not be connected yet');

        try {
            $this->database->query('SELECT 1');
        } catch (DatabaseException $e) {
            $this->markTestSkipped('Requires server running on localhost:3306 - ' . $e->getMessage());
        }

        $this->assertTrue($this->database->isConnected(), 'We should be connected now');
    }

    // requires server running on localhost:3306
    public function testMySqlDatabase()
    {
        // test performance data
        $performanceData = $this->database->getPerformanceData();
        $this->assertEquals(0, $performanceData['queries']);
        $this->assertEquals(0, $performanceData['time']);

        try {
            $this->database->query('SELECT 1');
        } catch (DatabaseException $e) {
            $this->markTestSkipped('Requires server running on localhost:3306 - ' . $e->getMessage());
        }

        $res = $this->database->select('test', '*');
        foreach ($res as $i => $row) {
            #var_dump($row);
        }
        $res->free();

        $res = $this->database->select('test', '*');
        while ($row = $res->fetchRow()) {
            #var_dump($row);
        }
        $res->free();

        $row = $this->database->selectRow('test', '*', ['id' => 2]);
        #var_dump($row);

        $row = $this->database->selectField('test', 'count(*)');
        #var_dump($row);

        $res = $this->database->query('SELECT VERSION()');
        #var_dump($res->fetchField());

        $performanceData = $this->database->getPerformanceData();
        $this->assertEquals(5, $performanceData['queries']);
        $this->assertTrue($performanceData['time'] > 0);
    }
}
