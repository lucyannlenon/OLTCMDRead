<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\Dto;

final readonly class OpticalInfo
{
    public function __construct(
        public ?string $voltage,
        public ?string $txOpticalPower,
        public ?string $rxOpticalPower,
        public ?string $laserBiasCurrent,
        public ?string $temperature,
    ) {
    }
}
