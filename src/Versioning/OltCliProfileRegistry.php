<?php

namespace LLENON\OltInformation\Versioning;

use LLENON\OltInformation\Capabilities\OltFeature;
use LLENON\OltInformation\DTO\OLT;
use LLENON\OltInformation\Enum\OltCliProfile;
use LLENON\OltInformation\Enum\OltModel;
use LLENON\OltInformation\Exceptions\IncompatibleOltCliProfileException;
use LLENON\OltInformation\Exceptions\UnknownOltCliProfileException;
use LLENON\OltInformation\Exceptions\MissingOltVersionConfigurationException;

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

        return $profile;
    }

    /**
     * @return list<OltCliProfileDefinition>
     */
    public function all(): array
    {
        return array_values($this->profiles);
    }

    /**
     * @return array<OltCliProfileDefinition>
     */
    private static function defaultProfiles(): array
    {
        return [
            new OltCliProfileDefinition(
                OltCliProfile::CDATA_EPON_CLI_V1,
                OltModel::CDATA,
                ['V1.6.5_250321'],
                'ssh',
                22,
                'device',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::FIRMWARE_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::LEARNED_MACS,
                    OltFeature::REVERSE_MAC_LOOKUP,
                    OltFeature::ROUTER_MAC_DISCOVERY,
                ]
            ),
            new OltCliProfileDefinition(
                OltCliProfile::DATACOM_DM461X_CLI_V1,
                OltModel::DATACOM,
                [
                    '9.4.2-042-1-g6453973b4e',
                    '8.6.4-001-1-g5fd3d06d49',
                    '8.0.2-020-1-g9e7efe4b92',
                ],
                'ssh',
                22,
                'device',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::FIRMWARE_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::ONU_STATUS,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::DISTANCE,
                    OltFeature::ETHERNET_STATE,
                    OltFeature::VLAN,
                    OltFeature::UNAUTHORIZED_ONUS,
                    OltFeature::LEARNED_MACS,
                    OltFeature::REVERSE_MAC_LOOKUP,
                    OltFeature::ROUTER_MAC_DISCOVERY,
                ]
            ),
            new OltCliProfileDefinition(
                OltCliProfile::ZTE_C610_CLI_V1,
                OltModel::ZTE,
                ['V1.2.2'],
                'ssh',
                22,
                'device',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::FIRMWARE_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::ONU_STATUS,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::DISTANCE,
                    OltFeature::ETHERNET_STATE,
                    OltFeature::VLAN,
                    OltFeature::UNAUTHORIZED_ONUS,
                    OltFeature::LEARNED_MACS,
                    OltFeature::REVERSE_MAC_LOOKUP,
                    OltFeature::ROUTER_MAC_DISCOVERY,
                ]
            ),
            new OltCliProfileDefinition(
                OltCliProfile::FIBERHOME_TL1_CLI_V1,
                OltModel::FIBERHOME,
                [],
                'tl1',
                3337,
                'shared_gateway',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::ONU_STATUS,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::DISTANCE,
                    OltFeature::ETHERNET_STATE,
                    OltFeature::VLAN,
                    OltFeature::UNAUTHORIZED_ONUS,
                    OltFeature::LEARNED_MACS,
                ],
                false
            ),
            new OltCliProfileDefinition(
                OltCliProfile::VSOL_EPON_CLI_V1,
                OltModel::VSOL,
                ['V1.01.51_230922190137', 'V1.01.51_220318155431'],
                'telnet',
                23,
                'device',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::FIRMWARE_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::ONU_STATUS,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::DISTANCE,
                    OltFeature::UPTIME,
                    OltFeature::ETHERNET_STATE,
                    OltFeature::LEARNED_MACS,
                    OltFeature::REVERSE_MAC_LOOKUP,
                    OltFeature::ROUTER_MAC_DISCOVERY,
                ]
            ),
            new OltCliProfileDefinition(
                OltCliProfile::VSOL_GPON_CLI_V2,
                OltModel::VSOLGPON,
                ['V2.1.8R'],
                'ssh',
                22,
                'device',
                [
                    OltFeature::CONNECTION_DIAGNOSTIC,
                    OltFeature::FIRMWARE_DIAGNOSTIC,
                    OltFeature::ONU_LIST,
                    OltFeature::ONU_LOOKUP,
                    OltFeature::ONU_STATUS,
                    OltFeature::OPTICAL_SIGNAL,
                    OltFeature::TEMPERATURE,
                    OltFeature::DISTANCE,
                    OltFeature::UPTIME,
                    OltFeature::ETHERNET_STATE,
                    OltFeature::ETHERNET_SPEED,
                    OltFeature::LEARNED_MACS,
                    OltFeature::REVERSE_MAC_LOOKUP,
                    OltFeature::ROUTER_MAC_DISCOVERY,
                ]
            ),
        ];
    }
}
