<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Support\MacAddress;

final class LearnedMacAddressStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*([0-9A-F]{4}[.:][0-9A-F]{4}[.:][0-9A-F]{4})\s+'
                . '(\d+)\s+(\S+)\s+GPON0\/(\d+):(\d+)\s+(\d+)\s+(\d+)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = new LearnedMacAddress(
                MacAddress::normalize($matches[1]),
                $matches[2],
                "0/{$matches[4]}",
                $matches[5],
                strtolower($matches[3]),
                $matches[6],
                $matches[7]
            );
        }

        return $results;
    }
}
