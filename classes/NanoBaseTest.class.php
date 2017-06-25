<?php

namespace Nano;

/**
 * Base class for PHPUnit-based unit tests
 */
class NanoBaseTest extends \PHPUnit_Framework_TestCase {

	/* @var $app /NanoApp */
	protected $app;

	public function setUp() {
		// use the current working directory where "./composer.phar test" is run
		$dir = getcwd();
		$this->app = \Nano::app($dir);
	}
}
