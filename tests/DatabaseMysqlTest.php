<?php

use Nano\NanoBaseTest;

/**
 * Requires MySQL server running on 0.0.0.0:3306
 */
class DatabaseMysqlTest extends NanoBaseTest
{
    /**
     * @var DatabaseMysql|null $database
     */
    private $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = Database::connect($this->app, ['driver' => 'mysql', 'host' => '0.0.0.0', 'user' => 'root', 'pass' => '']);
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

    public function testMySqlDatabase()
    {
        // test performance data
        $performanceData = $this->database->getPerformanceData();
        $this->assertEquals(0, $performanceData['queries']);

        try {
            $this->database->query('SELECT 1');
        } catch (DatabaseException $e) {
            $this->markTestSkipped('Requires server running on localhost:3306 - ' . $e->getMessage());
        }

        // DUAL is purely for the convenience of people who require that all SELECT statements
        // should have FROM and possibly other clauses. MySQL may ignore the clauses.
        // MySQL does not require FROM DUAL if no tables are referenced.
        $this->assertEquals('1', $this->database->selectField('dual', '1'));
        $this->assertEquals(1, $this->database->select('dual', '1')->count());

        $performanceData = $this->database->getPerformanceData();
        $this->assertEquals(3, $performanceData['queries']);
    }
}
