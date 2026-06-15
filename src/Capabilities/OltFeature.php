<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Capabilities;

final class OltFeature
{
    public const CONNECTION_DIAGNOSTIC = 'connection_diagnostic';
    public const FIRMWARE_DIAGNOSTIC = 'firmware_diagnostic';
    public const ONU_LIST = 'onu_list';
    public const ONU_LOOKUP = 'onu_lookup';
    public const ONU_STATUS = 'onu_status';
    public const OPTICAL_SIGNAL = 'optical_signal';
    public const TEMPERATURE = 'temperature';
    public const DISTANCE = 'distance';
    public const UPTIME = 'uptime';
    public const ETHERNET_STATE = 'ethernet_state';
    public const ETHERNET_SPEED = 'ethernet_speed';
    public const VLAN = 'vlan';
    public const UNAUTHORIZED_ONUS = 'unauthorized_onus';
    public const LEARNED_MACS = 'learned_macs';
    public const REVERSE_MAC_LOOKUP = 'reverse_mac_lookup';
    public const ROUTER_MAC_DISCOVERY = 'router_mac_discovery';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CONNECTION_DIAGNOSTIC,
            self::FIRMWARE_DIAGNOSTIC,
            self::ONU_LIST,
            self::ONU_LOOKUP,
            self::ONU_STATUS,
            self::OPTICAL_SIGNAL,
            self::TEMPERATURE,
            self::DISTANCE,
            self::UPTIME,
            self::ETHERNET_STATE,
            self::ETHERNET_SPEED,
            self::VLAN,
            self::UNAUTHORIZED_ONUS,
            self::LEARNED_MACS,
            self::REVERSE_MAC_LOOKUP,
            self::ROUTER_MAC_DISCOVERY,
        ];
    }

    public static function assertValid(string $feature): void
    {
        if (!in_array($feature, self::all(), true)) {
            throw new \InvalidArgumentException("Unknown OLT feature '{$feature}'.");
        }
    }

    private function __construct()
    {
    }
}
