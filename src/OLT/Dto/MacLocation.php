<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Dto;

final readonly class MacLocation
{
    public string $macAddress;

    public function __construct(
        string $macAddress,
        public string $pon,
        public string $onuId,
        public ?string $vlan = null,
        public ?string $type = null,
        public ?string $uniPort = null,
    ) {
        $this->macAddress = self::normalizeMacAddress($macAddress);

        if (trim($pon) === '' || trim($onuId) === '') {
            throw new \InvalidArgumentException('MAC location requires PON and ONU ID.');
        }
    }

    public static function normalizeMacAddress(string $macAddress): string
    {
        $hex = strtoupper(preg_replace('/[^0-9A-F]/i', '', $macAddress) ?? '');

        if (strlen($hex) !== 12) {
            throw new \InvalidArgumentException("Invalid MAC address '{$macAddress}'.");
        }

        return implode(':', str_split($hex, 2));
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'macAddress' => $this->macAddress,
            'pon' => $this->pon,
            'onuId' => $this->onuId,
            'vlan' => $this->vlan,
            'type' => $this->type,
            'uniPort' => $this->uniPort,
        ];
    }
}
