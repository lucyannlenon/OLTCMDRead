<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Dto;

use LLENON\OltInformation\OLT\Dto\MacLocation;

final readonly class MacTableEntry
{
    public string $macAddress;

    public function __construct(
        public int $servicePort,
        string $macAddress,
        public string $vlan,
        public string $type
    ) {
        $this->macAddress = MacLocation::normalizeMacAddress($macAddress);
    }
}
