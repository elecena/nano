<?php

namespace Nano\Logger;

use DateTimeInterface;
use Monolog\Handler\SyslogUdpHandler;

/**
 * Nano's wrapper for Monolog's SyslogUdpHandler that only emits priority and the message itself
 *
 * @see https://github.com/Seldaek/monolog/pull/943
 */
class SimpleSyslogUdpHandler extends SyslogUdpHandler
{
    /**
     * Make common syslog header (see rfc5424)
     *
     * @param int $severity
     * @param DateTimeInterface $datetime
     * @return string
     */
    protected function makeCommonSyslogHeader(int $severity, DateTimeInterface $datetime): string
    {
        $priority = $severity + $this->facility;

        return "<$priority>1 ";
    }
}
