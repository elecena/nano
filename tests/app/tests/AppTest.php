<?php

namespace Nano\AppTests;

use Nano\NanoBaseTest;
use Nano\Request;

/**
 * Generic class for unit tests for Nano's Application class
 */
abstract class AppTest extends NanoBaseTest
{

    /* @var \NanoApp $app */
    protected $app;
    protected $dir;
    protected $ip;

    public function setUp(): void
    {
        // client's IP
        $this->ip = '66.249.66.248';

        // fake request's data
        $params = [
            'q' => 'lm317',
        ];

        $env = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/test/?q=word',
            'HTTP_CLIENT_IP' => $this->ip,
        ];

        $this->dir = realpath(__DIR__ . '/..');
        $this->app = \Nano::app($this->dir);

        $request = new Request($params, $env);
        $this->app->setRequest($request);
    }
}
