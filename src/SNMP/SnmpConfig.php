<?php

namespace LLENON\OltInformation\SNMP;

final class SnmpConfig
{
    public function __construct(
        public readonly string $community,
        public readonly int $port = 161,
        public readonly int $timeoutSeconds = 3,
        public readonly int $retries = 1,
        public readonly string $version = '2c'
    ) {
    }
}

