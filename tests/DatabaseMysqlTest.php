<?php

use Nano\NanoBaseTest;

class DatabaseMysqlTest extends NanoBaseTest
{
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
