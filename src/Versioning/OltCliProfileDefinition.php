<?php

namespace LLENON\OltInformation\Versioning;

final readonly class OltCliProfileDefinition
{
    /**
     * @param array<string> $firmwareVersions
     */
    public function __construct(
        public string $id,
        public string $model,
        public array $firmwareVersions
    ) {
        if ($this->id === '' || $this->model === '') {
            throw new \InvalidArgumentException('OLT CLI profile ID and model are required.');
        }

        if ($this->firmwareVersions === []) {
            throw new \InvalidArgumentException('An OLT CLI profile requires at least one firmware version.');
        }
    }

    public function supportsFirmware(string $firmwareVersion): bool
    {
        return in_array(
            self::normalizeFirmwareVersion($firmwareVersion),
            array_map(self::normalizeFirmwareVersion(...), $this->firmwareVersions),
            true
        );
    }

    public static function normalizeFirmwareVersion(string $firmwareVersion): string
    {
        return strtoupper(trim($firmwareVersion));
    }
}
