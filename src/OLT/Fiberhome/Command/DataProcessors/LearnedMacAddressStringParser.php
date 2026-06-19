<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\OLT\Dto\MacLocation;

final class LearnedMacAddressStringParser extends AbstractStringParser
{
    protected function localParse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            $entry = $this->parseLine($line);
            if ($entry !== null) {
                $results[] = $entry;
            }
        }

        return $results;
    }

    /**
     * @return array{
     *     macAddress: string,
     *     vlan: string,
     *     type: string,
     *     gemIndex: ?string,
     *     gemId: ?string,
     *     uniPort: ?string
     * }|null
     */
    private function parseLine(string $line): ?array
    {
        $line = trim(str_replace('"', '', $line));
        if ($line === '' || $this->isFramingLine($line)) {
            return null;
        }

        $macAddress = $this->extractMacAddress($line);
        if ($macAddress === null) {
            return null;
        }

        return [
            'macAddress' => $macAddress,
            'vlan' => $this->extractVlan($line, $macAddress),
            'type' => $this->extractType($line),
            'gemIndex' => $this->extractGemIndex($line),
            'gemId' => $this->extractGemId($line),
            'uniPort' => $this->extractUniPort($line),
        ];
    }

    private function isFramingLine(string $line): bool
    {
        $normalized = strtoupper(trim($line));

        return $normalized === ''
            || preg_match('/^[=\-+_]+$/', $normalized) === 1
            || str_starts_with($normalized, 'M ')
            || str_contains($normalized, 'COMPLD')
            || str_starts_with($normalized, 'EN=')
            || str_starts_with($normalized, 'ENDESC=')
            || str_starts_with($normalized, 'EADD=')
            || str_contains($normalized, 'MACADDR')
            || str_contains($normalized, 'MAC ADDRESS');
    }

    private function extractMacAddress(string $line): ?string
    {
        if (preg_match(
            '/([0-9A-F]{12}|[0-9A-F]{4}\.[0-9A-F]{4}\.[0-9A-F]{4}|[0-9A-F]{2}(?:[:-][0-9A-F]{2}){5})/i',
            $line,
            $matches
        ) !== 1) {
            return null;
        }

        try {
            return MacLocation::normalizeMacAddress($matches[1]);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function extractVlan(string $line, string $macAddress): string
    {
        if (preg_match('/\bVLAN(?:ID)?\s*[:=]\s*(\d{1,4})\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        $tokens = $this->tokens($line);
        $macIndex = array_search($macAddress, array_map(
            static function (string $token): string {
                try {
                    return MacLocation::normalizeMacAddress($token);
                } catch (\InvalidArgumentException) {
                    return $token;
                }
            },
            $tokens
        ), true);

        if ($macIndex === false) {
            return '';
        }

        for ($i = $macIndex + 1; $i < min(count($tokens), $macIndex + 4); $i++) {
            if (preg_match('/^\d{1,4}$/', $tokens[$i]) === 1) {
                return $tokens[$i];
            }
        }

        return '';
    }

    private function extractType(string $line): string
    {
        if (preg_match('/\b(dynamic|static)\b/i', $line, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'dynamic';
    }

    private function extractGemIndex(string $line): ?string
    {
        if (preg_match('/\bGEM(?:INDEX)?\s*[:=]\s*([A-Z0-9._-]+)\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function extractGemId(string $line): ?string
    {
        if (preg_match('/\bGEMID\s*[:=]\s*([A-Z0-9._-]+)\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\bGEM[-\s]?(\d+)\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function extractUniPort(string $line): ?string
    {
        if (preg_match('/\bUNI(?:PORT)?\s*[:=]\s*([A-Z0-9._-]+)\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\bUNI[-\s]?([A-Z0-9._-]+)\b/i', $line, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $line): array
    {
        return array_values(array_filter(
            preg_split('/\s{2,}|\t+|\|/', trim($line)) ?: [],
            static fn (string $token): bool => trim($token) !== ''
        ));
    }
}
