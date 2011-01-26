<?php

/**
 * Wrapper for PHPUnit_TextUI_ResultPrinter class
 *
 * $Id$
 */

class ResultPrinter extends PHPUnit_TextUI_ResultPrinter {

	function __construct() {
		parent::__construct(null /* $out */, true /* $verbose */, false /* $colors */, false /* $debug */);
	}

	/**
     * A test suite started.
     *
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {

		var_dump(get_class($suite));
		var_dump($suite->count());

		if ($suite instanceof PHPUnit_Framework_TestCase) {
			$name = $suite->getName();
			echo "\n[{$name}]\n";
		}
	}
	/**/
}