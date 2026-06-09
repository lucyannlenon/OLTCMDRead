<?php

namespace LLENON\OltInformation\OLT\Dto;

final readonly class LearnedMacAddress
{
    public function __construct(
        public string $macAddress,
        public string $vlan,
        public string $pon,
        public string $onuId,
        public string $type,
        public ?string $gemIndex = null,
        public ?string $gemId = null
    ) {
    }
}
