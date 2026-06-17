<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\DataProcessors;

use LLENON\OltInformation\OLT\CDATA\Dto\OntInfo;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

/**
 * Parses the header of "show ont info <frame/slot> <port> <onu-id>". That
 * command returns a verbose dump (profiles, queues, eth ports); only the
 * leading status fields are relevant here.
 */
final class OntInfoStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $fields = [
            'runState' => null,
            'distance' => null,
            'lastUpTime' => null,
            'macAddress' => null,
        ];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$label, $value] = array_map('trim', explode(':', $line, 2));
            $normalized = strtolower($label);

            switch ($normalized) {
                case 'run state':
                    $fields['runState'] ??= strtolower($value);
                    break;
                case 'ont distance':
                    // "429m" -> "429"
                    $fields['distance'] ??= preg_replace('/\D/', '', $value);
                    break;
                case 'last up time':
                    $fields['lastUpTime'] ??= $value;
                    break;
                case 'mac':
                    $fields['macAddress'] ??= strtoupper($value);
                    break;
            }
        }

        if (array_filter($fields, static fn (?string $value): bool => $value !== null && $value !== '') === []) {
            return [];
        }

        return [
            new OntInfo(
                $fields['runState'],
                $fields['distance'],
                $fields['lastUpTime'],
                $fields['macAddress'],
            ),
        ];
    }
}
