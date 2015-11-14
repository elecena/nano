<?php

namespace Nano\Logger\Processors;

/**
 * Formats the 'exception' field passed in $context when logging errors
 */
class ExceptionProcessor {
	/**
	 * @param  array $record
	 * @return array
	 */
	public function __invoke(array $record) {
		if (!empty($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
			/* @var \Exception $exception */
			$exception = $record['context']['exception'];

			$record['context']['exception'] = [
				'class' => get_class($exception),
				'message' => $exception->getMessage(),
				'code'  => $exception->getCode(),
				'trace' => array_map(function($item) {
					if (!empty($item['file'])) {
						return sprintf('%s:%d', $item['file'], $item['line']);
					}
					else {
						return sprintf('%s%s%s', $item['class'], $item['type'], $item['function']);
					}
				}, $exception->getTrace())
			];
		}

		return $record;
	}
}