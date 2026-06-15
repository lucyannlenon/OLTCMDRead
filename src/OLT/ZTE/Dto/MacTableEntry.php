<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\Dto;

use LLENON\OltInformation\OLT\Dto\MacLocation;

final readonly class MacTableEntry
{
    public string $macAddress;

    public function __construct(
        string $macAddress,
        public string $vlan,
        public string $type,
        public string $pon,
        public string $onuId,
        public string $vport
    ) {
        $this->macAddress = MacLocation::normalizeMacAddress($macAddress);
    }
}
