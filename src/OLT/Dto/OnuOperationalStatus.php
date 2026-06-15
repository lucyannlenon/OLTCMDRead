<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Dto;

final readonly class OnuOperationalStatus
{
    public function __construct(
        public string $state,
        public ?int $distanceMeters = null,
        public ?int $uptimeSeconds = null,
    ) {
        if (trim($state) === '') {
            throw new \InvalidArgumentException('ONU operational state cannot be empty.');
        }

        if ($distanceMeters !== null && $distanceMeters < 0) {
            throw new \InvalidArgumentException('ONU distance cannot be negative.');
        }

        if ($uptimeSeconds !== null && $uptimeSeconds < 0) {
            throw new \InvalidArgumentException('ONU uptime cannot be negative.');
        }
    }

    public function isOnline(): bool
    {
        return in_array(strtolower($this->state), ['online', 'working', 'up'], true);
    }

    /** @return array{state:string,distanceMeters:?int,uptimeSeconds:?int} */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'distanceMeters' => $this->distanceMeters,
            'uptimeSeconds' => $this->uptimeSeconds,
        ];
    }
}
