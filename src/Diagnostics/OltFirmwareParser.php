<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

final readonly class OltFirmwareParser
{
    public function extract(string|bool|null $output): ?string
    {
        if (!is_string($output) || trim($output) === '') {
            return null;
        }

        foreach ([
            '/^\s*Firmware version\s*:\s*(?<version>[^\s(]+)/mi',
            '/^\s*Software Version\s*:\s*(?<version>[^\s(]+)/mi',
            '/^\s*Software version\s*:\s*(?<version>[^\s(]+)/mi',
            '/^\s*Version\s*:\s*(?<version>[^\s(]+)/mi',
            '/^\s*BootROM Version\s*:\s*(?<version>[^\s(]+)/mi',
        ] as $pattern) {
            if (preg_match($pattern, $output, $matches) === 1) {
                $version = trim((string) ($matches['version'] ?? ''));
                if ($version !== '') {
                    return $version;
                }
            }
        }

        return null;
    }
}
