<?php

namespace Nano\AppTests;
use Nano\NanoBaseTest;

/**
 * Generic class for unit tests for Nano's Application class
 */
abstract class AppTest extends NanoBaseTest {

	/* @var \NanoApp $app */
	protected $app;
	protected $dir;
	protected $ip;

	public function setUp() {
		// client's IP
		$this->ip = '66.249.66.248';

		// fake request's data
		$_REQUEST = array(
			'q' => 'lm317',
		);

		$_SERVER = array(
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/foo/test/?q=word',
			'HTTP_CLIENT_IP' => $this->ip,
		);

		$this->dir = realpath(dirname(__FILE__) . '/..');
		$this->app = \Nano::app($this->dir);
	}
}
