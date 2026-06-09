<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Dto;

final readonly class MacLocation
{
    public function __construct(
        public string $macAddress,
        public string $vlan,
        public string $type,
        public string $port
    ) {
    }
}
