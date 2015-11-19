<?php

namespace Nano\Logger\Processors;

/**
 * Injects a unique per-request ID into extra fields of Monolog-generated log entry
 */
class RequestIdProcessor {
	/* @var string */
	static private $requestId = null;

	/**
	 * Get per-request unique ID
	 *
	 * Example: nano-5647673c1975c3.41794614
	 *
	 * @return string
	 */
	public function getRequestId() {
		if (self::$requestId === null) {
			self::$requestId = uniqid('nano-', true);
		}

		return self::$requestId;
	}

	/**
	 * @param  array $record
	 * @return array
	 */
	public function __invoke(array $record) {
		$record['extra']['request_id'] = self::getRequestId();
		return $record;
	}
}