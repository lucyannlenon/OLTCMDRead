<?php

namespace LLENON\OltInformation\Versioning;

use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\IncompatibleOltCliProfileException;
use LLENON\OltInformation\Exceptions\MissingOltVersionConfigurationException;
use LLENON\OltInformation\Exceptions\UnknownOltCliProfileException;
use LLENON\OltInformation\Exceptions\UnsupportedOltFirmwareException;

final class OltCliProfileRegistry
{
    /** @var array<string, OltCliProfileDefinition> */
    private array $profiles = [];

    /**
     * @param array<OltCliProfileDefinition>|null $profiles
     */
    public function __construct(?array $profiles = null)
    {
        foreach ($profiles ?? self::defaultProfiles() as $profile) {
            $this->profiles[$profile->id] = $profile;
        }
    }

    public function resolve(OLT $olt): OltCliProfileDefinition
    {
        if ($olt->cliProfile === null || trim($olt->cliProfile) === '') {
            throw new MissingOltVersionConfigurationException(
                "CLI profile is required for OLT model {$olt->model}."
            );
        }

        if ($olt->firmwareVersion === null || trim($olt->firmwareVersion) === '') {
            throw new MissingOltVersionConfigurationException(
                "Firmware version is required for OLT model {$olt->model}."
            );
        }

        $profile = $this->profiles[$olt->cliProfile] ?? null;
        if ($profile === null) {
            throw new UnknownOltCliProfileException(
                "Unknown OLT CLI profile '{$olt->cliProfile}'."
            );
        }

        if ($profile->model !== $olt->model) {
            throw new IncompatibleOltCliProfileException(
                "OLT CLI profile '{$profile->id}' does not support model '{$olt->model}'."
            );
        }

        if (!$profile->supportsFirmware($olt->firmwareVersion)) {
            throw new UnsupportedOltFirmwareException(
                "Firmware '{$olt->firmwareVersion}' is not homologated for CLI profile '{$profile->id}'."
            );
        }

        return $profile;
    }

    /**
     * @return array<OltCliProfileDefinition>
     */
    private static function defaultProfiles(): array
    {
        return [
            new OltCliProfileDefinition(
                OltCliProfile::VSOL_EPON_CLI_V1,
                OltModel::VSOL,
                ['V1.01.51_230922190137']
            ),
            new OltCliProfileDefinition(
                OltCliProfile::VSOL_GPON_CLI_V2,
                OltModel::VSOLGPON,
                ['V2.1.8R']
            ),
        ];
    }
}
