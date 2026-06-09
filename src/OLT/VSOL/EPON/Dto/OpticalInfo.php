<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Dto;

final readonly class OpticalInfo
{
    public function __construct(
        public string $temperature,
        public string $voltage,
        public string $laserBiasCurrent,
        public string $txOpticalLevel,
        public string $rxOpticalLevel
    ) {
    }
}
