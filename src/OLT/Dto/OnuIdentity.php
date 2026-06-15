<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Dto;

final readonly class OnuIdentity
{
    public function __construct(
        public string $model,
        public string $pon,
        public string $onuId,
        public string $registrationId,
        public string $state,
        public ?string $vendorAddress = null,
    ) {
        foreach ([
            'model' => $model,
            'pon' => $pon,
            'onuId' => $onuId,
            'registrationId' => $registrationId,
            'state' => $state,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new \InvalidArgumentException("ONU identity field '{$field}' cannot be empty.");
            }
        }
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'pon' => $this->pon,
            'onuId' => $this->onuId,
            'registrationId' => $this->registrationId,
            'state' => $this->state,
            'vendorAddress' => $this->vendorAddress,
        ];
    }
}
