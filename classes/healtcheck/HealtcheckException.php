<?php

namespace Nano\Healtcheck;

/**
 * Class HealtcheckException
 */
class HealthcheckException extends \Exception {
	private $check;

	/**
	 * @param string $check
	 */
	public function setCheck($check) {
		$this->check = $check;
	}

	/**
	 * @return string
	 */
	public function getCheck() {
		return $this->check;
	}
}
