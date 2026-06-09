<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Support;

final class MacAddress
{
    public static function normalize(string $macAddress): string
    {
        $hex = strtoupper(preg_replace('/[^0-9A-F]/i', '', $macAddress) ?? '');

        if (strlen($hex) !== 12) {
            throw new \InvalidArgumentException("Invalid MAC address '{$macAddress}'.");
        }

        return implode(':', str_split($hex, 2));
    }

    public static function forVSolCommand(string $macAddress): string
    {
        $hex = str_replace(':', '', self::normalize($macAddress));
        return implode(':', str_split($hex, 4));
    }

    private function __construct()
    {
    }
}
