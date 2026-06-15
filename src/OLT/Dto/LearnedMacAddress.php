<?php

namespace LLENON\OltInformation\OLT\Dto;

final readonly class LearnedMacAddress
{
    public string $macAddress;

    public function __construct(
        string $macAddress,
        public string $vlan,
        public string $pon,
        public string $onuId,
        public string $type,
        public ?string $gemIndex = null,
        public ?string $gemId = null,
        public ?string $uniPort = null
    ) {
        $this->macAddress = MacLocation::normalizeMacAddress($macAddress);
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'macAddress' => $this->macAddress,
            'vlan' => $this->vlan,
            'pon' => $this->pon,
            'onuId' => $this->onuId,
            'type' => $this->type,
            'gemIndex' => $this->gemIndex,
            'gemId' => $this->gemId,
            'uniPort' => $this->uniPort,
        ];
    }
}
