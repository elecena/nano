<?php
/**
 * Integration layer for domnikl/statsd library
 */

namespace Nano;

class Stats {

	/**
	 * @param \NanoApp $app application instance
	 * @param string $namespace option namespace to be appended to the global namespace
	 * @return \Domnikl\Statsd\Client StatsD client instance
	 */
	static function getCollector(\NanoApp $app, $namespace = '') {
		$config = $app->getConfig();

		$statsdEnabled = $config->get('stats', false) !== false;

		if ($statsdEnabled) {
			// get global config
			$host = $config->get('stats.host', 'localhost');
			$port = $config->get('stats.port', 8125);
			$globalNS = $config->get('stats.namespace', '');

			if ($globalNS != '') {
				$namespace = $globalNS . '.' . $namespace;
			}

			$connection = new \Domnikl\Statsd\Connection\Socket($host, $port);
		}
		else {
			// disable sending any metrics
			$connection = new \Domnikl\Statsd\Connection\Blackhole();
		}

		$statsd = new \Domnikl\Statsd\Client($connection, $namespace);

		return $statsd;
	}
}
