<?php

namespace Nano\Logger;

use Monolog\Handler\SyslogUdpHandler;

/**
 * Nano's wrapper for Monolog's SyslogUdpHandler that only emits priority and the message itself
 *
 * @see https://github.com/Seldaek/monolog/pull/943
 */
class SimpleSyslogUdpHandler extends SyslogUdpHandler {

	/**
	 * Make common syslog header (see rfc5424)
	 */
	protected function makeCommonSyslogHeader($severity)
	{
		$priority = $severity + $this->facility;

		return "<$priority>1 ";
	}
}
