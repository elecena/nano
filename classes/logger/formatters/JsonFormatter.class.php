<?php

namespace Nano\Logger\Formatters;

/**
 * Custom Nano JSON formatter
 */
class JsonFormatter extends \Monolog\Formatter\JsonFormatter {

	/**
	 * @param array $record
	 * @return string formatted record
	 */
	public function format(array $record) {
		$entry = [
			'@timestamp' => gmdate('c'),
			'@message' => $record['message'],
			'@context' => $record['context'],
			'@fields' => $record['extra'],
			'severity' => strtolower($record['level_name']),
			'program' => $record['channel'],
			'@source_host' => gethostname(),
		];

		return parent::format($entry);
	}
}
