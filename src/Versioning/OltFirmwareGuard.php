<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Versioning;

use LLENON\OltInformation\Diagnostics\OltFirmwareParser;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Exceptions\UnsupportedOltFirmwareException;

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

    public function assertDetectedVersion(OLT $olt, string|bool|null $output): string
    {
        $detected = $this->parser->extract($output);
        if ($detected === null) {
            throw new UnsupportedOltFirmwareException(
                "Unable to detect connected firmware for OLT model '{$olt->model}'."
            );
        }

        if (OltCliProfileDefinition::normalizeFirmwareVersion($detected)
            !== OltCliProfileDefinition::normalizeFirmwareVersion((string) $olt->firmwareVersion)) {
            throw new UnsupportedOltFirmwareException(
                "Connected firmware does not match the configured firmware version."
            );
        }

        return $detected;
    }
}
