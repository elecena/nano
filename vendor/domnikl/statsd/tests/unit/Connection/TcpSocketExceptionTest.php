<?php

namespace Domnikl\Test\Statsd\Connection;

use Domnikl\Statsd\Connection\TcpSocketException;

class TcpSocketExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCanGetMessage()
    {
        $e = new TcpSocketException('localhost', 666, 'Connection refused');
        $this->assertEquals('Couldn\'t connect to host "localhost:666": Connection refused', $e->getMessage());
    }
}
