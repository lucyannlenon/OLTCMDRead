<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Dto;

final readonly class OnuOpticalMetrics
{
    public function __construct(
        public ?float $rxPowerDbm = null,
        public ?float $txPowerDbm = null,
        public ?float $temperatureCelsius = null,
        public ?float $voltage = null,
        public ?float $laserBiasCurrent = null,
    ) {
        foreach ([
            $rxPowerDbm,
            $txPowerDbm,
            $temperatureCelsius,
            $voltage,
            $laserBiasCurrent,
        ] as $value) {
            if ($value !== null && !is_finite($value)) {
                throw new \InvalidArgumentException('ONU optical metrics must be finite numbers.');
            }
        }
    }

    /** @return array<string, float|null> */
    public function toArray(): array
    {
        return [
            'rxPowerDbm' => $this->rxPowerDbm,
            'txPowerDbm' => $this->txPowerDbm,
            'temperatureCelsius' => $this->temperatureCelsius,
            'voltage' => $this->voltage,
            'laserBiasCurrent' => $this->laserBiasCurrent,
        ];
    }
}
