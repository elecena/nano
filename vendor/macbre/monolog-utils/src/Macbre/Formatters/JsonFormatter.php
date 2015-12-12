<?php

namespace Macbre\Logger\Formatters;

/**
 * Custom Nano JSON formatter
 */
class JsonFormatter extends \Monolog\Formatter\JsonFormatter {

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html#mapping-date-format
	 */
	const DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

	/**
	 * @param array $record
	 * @return string formatted record
	 */
	public function format(array $record) {
		$entry = [
			'@timestamp' => self::now(),
			'@message' => $record['message'],
			'@context' => (object) $record['context'],
			'@fields' => (object) $record['extra'],
			'severity' => strtolower($record['level_name']),
			'program' => $record['channel'],
			'@source_host' => gethostname(),
		];

		return parent::format($entry);
	}

	/**
	 * Return a elasticsearch compatible timestamp with microseconds
	 *
	 * @see http://stackoverflow.com/a/17909891/5446110
	 * @return string
	 */
	private static function now() {
		$ret = date(self::DATE_FORMAT);

		// add microseconds
		$t = microtime(true);
		$micro = sprintf("%06d",($t - floor($t)) * 1000000);

		$ret = str_replace('000000', $micro, $ret);
		return $ret;
	}
}
