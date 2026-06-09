<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Dto;

final readonly class OnuStatus
{
    public function __construct(
        public string $adminState,
        public string $omccState,
        public string $phaseState,
        public string $channel
    ) {
    }

    public function isOnline(): bool
    {
        return strtolower($this->phaseState) === 'working';
    }
}
