<?php

namespace Nano\Healtcheck;

use Nano\NanoObject;
use Nano\Response;

/**
 * Perform health checks
 *
 * Methods with check prefix will be executed.
 * Failed check should be signaled with HealtcheckException
 */
abstract class HealthcheckBase extends NanoObject {

	private $response;
	private $currentCheck;

	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		$this->response = $this->app->getResponse();
		$this->response->setHeader('X-HealthCheck', 1);
		$this->response->setContentType('text/plain');
	}

	/**
	 * Do not connect to the database be default!
	 *
	 * @param \NanoApp $app
	 * @return \Database|null
	 */
	static protected function getDatabase(\NanoApp $app) {
		return null;
	}

	/**
	 * @param string $msg
	 */
	protected function log($msg) {
		$this->debug->log(__CLASS__ . ': ' . $msg);
	}

	/**
	 * Return the list of all checks from the current healthcheck class
	 *
	 * @return array
	 */
	private function getChecksList() {
		$methods = get_class_methods(static::class);

		return array_filter($methods, function($method) {
			return strpos($method, 'check') === 0;
		});
	}

	/**
	 * Indicate that the check is fine
	 */
	private function ok() {
		$this->response->setResponseCode(Response::OK);
		$this->response->setContent("OK\n");
	}

	/**
	 * Indicate that the check failed
	 *
	 * @param HealthcheckException $ex
	 */
	private function failure(HealthcheckException $ex) {
		$this->logger->error('HealthcheckException', [
			'exception' => $ex,
			'check' => $ex->getCheck(),
		]);

		$this->response->setResponseCode(Response::SERVICE_UNAVAILABLE);
		$this->response->setHeader('X-Check-Failed', $ex->getCheck());
		$this->response->setContent($ex->getMessage() . "\n");
	}

	/**
	 * Perform a series of checks
	 *
	 * @retunr string
	 */
	public function run() {
		$check = 'UNKNOWN';

		try {
			foreach($this->getChecksList() as $check) {
				$this->currentCheck = $check;
				$this->log($check);

				$this->$check();
			};

			$this->ok();
		}
		catch(HealthcheckException $e) {
			$this->failure($e);
		}
		catch(\Exception $e) {
			$this->failure(new HealthcheckException(
				sprintf('%s() - %s: %s', $check, get_class($e), $e->getMessage()),
				$e->getCode()
			));
		}

		return $this->response->render();
	}

	/**
	 * @param mixed $expected
	 * @param mixed $actual
	 * @param string $msg
	 * @throws HealthcheckException
	 */
	protected function assertEquals($expected, $actual, $msg) {
		if ($expected !== $actual) {
			$this->log($msg);
			$this->log('Expected: ' . json_encode($expected));
			$this->log('Actual:   ' . json_encode($actual));

			$ex = new HealthcheckException($msg);
			$ex->setCheck($this->currentCheck);

			throw $ex;
		}
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param string $msg
	 * @throws HealthcheckException
	 */
	protected function assertContains($haystack, $needle, $msg) {
		$this->assertEquals(true, stripos($haystack, $needle) !== false, $msg);
	}

	/**
	 * @param bool $actual
	 * @param string $msg
	 * @throws HealthcheckException
	 */
	protected function assertTrue($actual, $msg) {
		$this->assertEquals(true, $actual, $msg);
	}
}
