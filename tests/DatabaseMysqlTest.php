<?php

use Nano\NanoBaseTest;

class DatabaseMysqlTest extends NanoBaseTest
{
    /**
     * @throws DatabaseException
     */
    public function testLazyConnect()
    {
        $database = Database::connect($this->app, ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'pass' => '', 'database' => 'test']);
        $this->assertInstanceOf(DatabaseMysql::class, $database);

        $this->assertFalse($database->isConnected(), 'We should not be connected yet');

        /**
        $database->query('SELECT 1 FROM dual');
        $this->assertTrue($database->isConnected(), 'We should be connected now');
         **/
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
