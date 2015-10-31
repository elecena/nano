<?php

namespace Nano\Tests;

/**
 * Wrapper for PHPUnit_TextUI_ResultPrinter class
 */
class ResultPrinter extends \PHPUnit_TextUI_ResultPrinter {

	private $level = 0;

	/**
	 *
	 */
	function __construct() {
		parent::__construct(null /* $out */, true /* $verbose */, 'auto' /* $colors */, false /* $debug */);

		$this->write(\PHPUnit_Runner_Version::getVersionString() . "\n");
		$this->write('NanoPortal v' . \Nano::VERSION . ' / PHP v' . phpversion() . "\n");
	}

	/**
     * A test suite started.
     *
	 * @param \PHPUnit_Framework_TestSuite $suite
	 */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		$name = $suite->getName();

		switch($this->level) {
			case 0:
				break;

			case 1:
				$this->write("\n{$name}:");
				break;

			case 2:
				$this->write("\n* {$name} ");
				break;
		}

		$this->level++;
	}

	 /**
     * A testsuite ended.
     *
	 * @param \PHPUnit_Framework_TestSuite $suite
	 */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
		$this->level--;

		if ($this->level == 1) {
			$this->write("\n");
		}
    }
}
