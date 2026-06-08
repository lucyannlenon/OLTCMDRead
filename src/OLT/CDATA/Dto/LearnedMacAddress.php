<?php

namespace LLENON\OltInformation\OLT\CDATA\Dto;

final readonly class LearnedMacAddress
{
    public function __construct(
        public string $macAddress,
        public string $vlan,
        public string $pon,
        public string $onuId,
        public string $type
    ) {
    }
}
