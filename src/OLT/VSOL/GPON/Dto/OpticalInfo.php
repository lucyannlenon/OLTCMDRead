<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Dto;

final readonly class OpticalInfo
{
    public function __construct(
        public string $rxOpticalLevel,
        public string $txOpticalLevel,
        public string $temperature,
        public string $voltage,
        public string $laserBiasCurrent
    ) {
    }
}
