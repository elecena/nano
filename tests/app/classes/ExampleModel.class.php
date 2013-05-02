<?php

/**
 * Dummy class for app testing
 */

class ExampleModel {

	public $app;
	public $foo;
	public $bar;

	function __construct(NanoApp $app, $foo = null, $bar = null) {
		$this->app = $app;

		$this->foo = $foo;
		$this->bar = $bar;
	}
}