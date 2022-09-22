<?php

use Nano\MessageQueue;

/**
 * Set of unit tests for MessageQueue class
 */

class MessageQueueTest extends \Nano\NanoBaseTest
{
    private function getMessageQueue($driver, $settings = [])
    {
        // use test application's directory
        $dir = realpath(__DIR__ . '/app');
        $app = Nano::app($dir);

        $settings = array_merge([
            'driver' => $driver,
        ], $settings);

        return MessageQueue::connect($app, $settings);
    }

    public function testMessageQueueFactory()
    {
        $this->assertInstanceOf('Nano\Mq\MessageQueueRedis', $this->getMessageQueue('redis'));
    }
}
