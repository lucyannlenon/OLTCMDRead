<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Capabilities;

final class OltFeatureState
{
    public const SUPPORTED = 'supported';
    public const UNAVAILABLE = 'unavailable';
    public const UNSUPPORTED = 'unsupported';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SUPPORTED,
            self::UNAVAILABLE,
            self::UNSUPPORTED,
        ];
    }

    public static function assertValid(string $state): void
    {
        if (!in_array($state, self::all(), true)) {
            throw new \InvalidArgumentException("Unknown OLT feature state '{$state}'.");
        }
    }

    private function __construct()
    {
    }
}
