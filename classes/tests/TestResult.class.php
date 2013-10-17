<?php

/**
 * Wrapper for PHPUnit_Framework_TestResult class
 */

namespace Nano\Tests;

class TestResult extends \PHPUnit_Framework_TestResult {

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
				else switch($status) {
					// line not covered - function block ends here and there's return before
					case -2:
						$linesTotal -= 1;
						break;

					// line not covered
					case -1:
						$notCoveredLines[] = $lineNo;
						break;
				}
			}

			$file = str_replace(\Nano::getCoreDirectory(), '', $file);

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