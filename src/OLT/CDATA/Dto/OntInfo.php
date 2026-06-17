<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\Dto;

final readonly class OntInfo
{
    public function __construct(
        public ?string $runState,
        public ?string $distance,
        public ?string $lastUpTime,
        public ?string $macAddress,
    ) {
    }
}
