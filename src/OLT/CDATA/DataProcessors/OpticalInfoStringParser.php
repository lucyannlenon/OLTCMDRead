<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\DataProcessors;

use LLENON\OltInformation\OLT\CDATA\Dto\OpticalInfo;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class OpticalInfoStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $metrics = [
            'voltage' => null,
            'txOpticalPower' => null,
            'rxOpticalPower' => null,
            'laserBiasCurrent' => null,
            'temperature' => null,
        ];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$label, $value] = array_map('trim', explode(':', $line, 2));
            $normalized = strtolower($label);

            switch ($normalized) {
                case 'voltage(v)':
                    $metrics['voltage'] = $value;
                    break;
                case 'tx optical power(dbm)':
                    $metrics['txOpticalPower'] = $value;
                    break;
                case 'rx optical power(dbm)':
                    $metrics['rxOpticalPower'] = $value;
                    break;
                case 'laser bias current(ma)':
                    $metrics['laserBiasCurrent'] = $value;
                    break;
                case 'temperature(c)':
                    $metrics['temperature'] = $value;
                    break;
            }
        }

        if (array_filter($metrics, static fn (?string $value): bool => $value !== null) === []) {
            return [];
        }

        return [
            new OpticalInfo(
                $metrics['voltage'],
                $metrics['txOpticalPower'],
                $metrics['rxOpticalPower'],
                $metrics['laserBiasCurrent'],
                $metrics['temperature']
            ),
        ];
    }
}
