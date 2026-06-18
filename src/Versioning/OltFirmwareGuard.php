<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Versioning;

use LLENON\OltInformation\Diagnostics\OltFirmwareParser;
use LLENON\OltInformation\DTO\OLT;
final readonly class OltFirmwareGuard
{
    public function __construct(
        private OltCliProfileRegistry $profiles = new OltCliProfileRegistry(),
        private OltFirmwareParser $parser = new OltFirmwareParser(),
    ) {
    }

    public function validateConfiguration(OLT $olt): OltCliProfileDefinition
    {
        return $this->profiles->resolve($olt);
    }

    public function assertDetectedVersion(OLT $olt, string|bool|null $output): ?string
    {
        $detected = $this->parser->extract($output);
        if ($detected === null) {
            return null;
        }

        if ($olt->firmwareVersion === null || trim($olt->firmwareVersion) === '') {
            return $detected;
        }

        if (OltCliProfileDefinition::normalizeFirmwareVersion($detected)
            !== OltCliProfileDefinition::normalizeFirmwareVersion((string) $olt->firmwareVersion)) {
            return $detected;
        }

        return $detected;
    }
}
