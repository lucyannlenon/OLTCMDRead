<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Dto;

final readonly class OnuStatus
{
    public function __construct(
        public string $pon,
        public int $onuId,
        public string $status,
        public string $macAddress,
        public string $distance,
        public string $aliveTime
    ) {
    }

    public function isOnline(): bool
    {
        return strtolower($this->status) === 'online';
    }
}
