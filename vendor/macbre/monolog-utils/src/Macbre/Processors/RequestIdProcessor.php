<?php

namespace Macbre\Logger\Processors;

/**
 * Injects a unique per-request ID into extra fields of Monolog-generated log entry
 */
class RequestIdProcessor {
	/* @var string */
	static private $requestId = null;

	/**
	 * Get per-request unique ID
	 *
	 * Example: 5654ba177058c8.07373029
	 *
	 * @return string
	 */
	public static function getRequestId() {
		if (self::$requestId === null) {
			self::$requestId = uniqid('', true);
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