<?php

/**
 * Wrapper for PHPUnit_Framework_TestResult class
 *
 * $Id$
 */

class TestResult extends PHPUnit_Framework_TestResult {

	function __construct() {
		parent::__construct();
	}

	/**
     * Returns the summarized coverage report
     */
    public function getCodeCoverageSummary() {
		$summary = array();
		$codeCoverageSummary = $this->getCodeCoverage()->getSummary();

        foreach($codeCoverageSummary as $file => $report) {
			$linesCovered = 0;
			$linesTotal = count($report);

			$notCoveredLines = array();

			foreach($report as $lineNo => $status) {
				// line tested
				if (is_array($status)) {
					$linesCovered++;
				}
				// line not covered - function block ends here and there's return before
				else if ($status == -2) {
					$linesTotal -= 1;
				}
				// line not covered
				else if ($status == -1) {
					$notCoveredLines[] = $lineNo;
				}
			}

			$file = str_replace(Nano::getCoreDirectory(), '', $file);

			$summary[$file] = array(
				'linesCovered' => $linesCovered,
				'linesTotal' => $linesTotal,
				'notCoveredLines' => implode(',', $notCoveredLines),
				'coverage' => round($linesCovered / $linesTotal * 100, 2),
			);
		}

		return $summary;
    }
}