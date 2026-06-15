<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Dto;

final readonly class OnuEthernetStatus
{
    public function __construct(
        public string $state,
        public ?int $speedMbps = null,
        public ?string $configuredSpeed = null,
        public ?string $loopState = null,
    ) {
        if (trim($state) === '') {
            throw new \InvalidArgumentException('ONU Ethernet state cannot be empty.');
        }

        if ($speedMbps !== null && $speedMbps <= 0) {
            throw new \InvalidArgumentException('ONU Ethernet speed must be positive.');
        }
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'speedMbps' => $this->speedMbps,
            'configuredSpeed' => $this->configuredSpeed,
            'loopState' => $this->loopState,
        ];
    }
}
