<?php

namespace LLENON\OltInformation\Versioning;

use LLENON\OltInformation\Capabilities\OltFeature;

final readonly class OltCliProfileDefinition
{
    /**
     * @param array<string> $firmwareVersions
     * @param list<string> $features
     */
    public function __construct(
        public string $id,
        public string $model,
        public array $firmwareVersions,
        public string $transport = 'ssh',
        public int $defaultPort = 22,
        public string $credentialScope = 'device',
        public array $features = [],
        public bool $requiresFirmware = true
    ) {
        if ($this->id === '' || $this->model === '') {
            throw new \InvalidArgumentException('OLT CLI profile ID and model are required.');
        }

        if ($this->requiresFirmware && $this->firmwareVersions === []) {
            throw new \InvalidArgumentException('An OLT CLI profile requires at least one firmware version.');
        }

        if (!in_array($transport, ['ssh', 'telnet', 'tl1'], true)) {
            throw new \InvalidArgumentException("Unsupported OLT profile transport '{$transport}'.");
        }

        if ($defaultPort < 1 || $defaultPort > 65535) {
            throw new \InvalidArgumentException('OLT profile port must be between 1 and 65535.');
        }

        foreach ($features as $feature) {
            OltFeature::assertValid($feature);
        }
    }

    public function supportsFirmware(string $firmwareVersion): bool
    {
        if (!$this->requiresFirmware) {
            return true;
        }

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
