<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Dto;

final readonly class EthernetStatus
{
    public function __construct(
        public string $speed,
        public string $status,
        public string $speedConfig,
        public string $loopStatus
    ) {
    }
}
