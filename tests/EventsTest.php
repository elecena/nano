<?php

use Nano\Events;

/**
 * Set of unit tests for Events class
 */

class EventsTest extends \Nano\NanoBaseTest
{
    public function setUp(): void
    {
        // use test application's directory
        $dir = realpath(__DIR__ . '/app');
        $this->app = Nano::app($dir);
    }

    public function testOnFire()
    {
        $events = new Events($this->app);
        $events->bind('event', [$this, 'handlerTrue']);

        $this->assertTrue($events->fire('event'));
        $this->assertTrue($events->fire('eventfoo'));
    }

    public function testOnFireStop()
    {
        $events = new Events($this->app);
        $events->bind('event', [$this, 'handlerFalse']);

        $this->assertFalse($events->fire('event'));
    }

    public function testOnFirePass()
    {
        $value = '0';

        $events = new Events($this->app);
        $events->bind('event', [$this, 'handlerTrue']);

        $this->assertTrue($events->fire('event', [&$value]));
        $this->assertEquals('0123', $value);
    }

    public function testOnFirePassQueue()
    {
        $value = '0';

        $events = new Events($this->app);
        $events->bind('event', [$this, 'handlerTrue']);
        $events->bind('event', [$this, 'handlerFalse']);

        $this->assertFalse($events->fire('event', [&$value]));
        $this->assertEquals('0123456', $value);
    }

    public function testOnFirePassStop()
    {
        $value = '0';

        $events = new Events($this->app);
        $events->bind('event', [$this, 'handlerFalse']);
        $events->bind('event', [$this, 'handlerTrue']);

        $this->assertFalse($events->fire('event', [&$value]));
        $this->assertEquals('0456', $value);
    }

    public function handlerTrue(&$foo = '')
    {
        $foo .= '123';
        return true;
    }

    public function handlerFalse(&$foo = '')
    {
        $foo .= '456';
        return false;
    }
}
