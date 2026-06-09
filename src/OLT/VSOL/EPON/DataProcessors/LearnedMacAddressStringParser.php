<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class LearnedMacAddressStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*\d+\s+(\d+)\s+([0-9A-F:]{17})\s+EPON0\/(\d+)\s+(\d+)\s+\d+\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = new LearnedMacAddress(
                MacAddress::normalize($matches[2]),
                $matches[1],
                "0/{$matches[3]}",
                $matches[4],
                'dynamic'
            );
        }

        return $results;
    }
}
