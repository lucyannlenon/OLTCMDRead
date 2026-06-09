<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\OpticalInfo;

final class OpticalInfoStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $values = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match('/^\s*([^:]+):\s*(.*?)\s*$/', $line, $matches)) {
                $values[strtolower(trim($matches[1]))] = trim($matches[2]);
            }
        }

        $required = [
            'rx optical level',
            'tx optical level',
            'temperature',
            'power feed voltage',
            'laser bias current',
        ];

        foreach ($required as $key) {
            if (!isset($values[$key])) {
                return [];
            }
        }

        return [
            new OpticalInfo(
                self::number($values['rx optical level']),
                self::number($values['tx optical level']),
                self::number($values['temperature']),
                self::number($values['power feed voltage']),
                self::number($values['laser bias current'])
            ),
        ];
    }

    private static function number(string $value): string
    {
        return preg_match('/-?\d+(?:\.\d+)?/', $value, $matches)
            ? $matches[0]
            : '';
    }
}
