<?php

declare(strict_types=1);

namespace LLENON\OltInformation\Diagnostics;

final class OltProbeSanitizer
{
    /** @var array<string, string> */
    private array $replacements = [];

    /**
     * @param list<string> $secrets
     */
    public function sanitize(string $value, array $secrets = []): string
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $value = str_replace($secret, '[REDACTED]', $value);
            }
        }

        $patterns = [
            ['IP', '/\b(?:\d{1,3}\.){3}\d{1,3}\b/'],
            ['MAC', '/\b[0-9A-F]{2}(?:(?::|-)[0-9A-F]{2}){5}\b/i'],
            ['MAC', '/\b[0-9A-F]{4}(?::|\.)[0-9A-F]{4}(?::|\.)[0-9A-F]{4}\b/i'],
            ['SERIAL', '/\b[A-Z]{4}[0-9A-F]{8,12}\b/i'],
        ];

        foreach ($patterns as [$type, $pattern]) {
            $value = preg_replace_callback(
                $pattern,
                fn (array $matches): string => $this->replacement($type, $matches[0]),
                $value
            ) ?? $value;
        }

        return $value;
    }

    private function replacement(string $type, string $value): string
    {
        $key = $type . ':' . strtoupper($value);
        if (!isset($this->replacements[$key])) {
            $index = 1 + count(array_filter(
                array_keys($this->replacements),
                static fn (string $candidate): bool => str_starts_with($candidate, $type . ':')
            ));
            $this->replacements[$key] = sprintf('[%s_%d]', $type, $index);
        }

        return $this->replacements[$key];
    }
}
