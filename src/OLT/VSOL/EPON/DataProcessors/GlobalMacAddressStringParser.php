<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class GlobalMacAddressStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*(\d+)\s+([0-9A-F:]{17})\s+(\S+)\s+epon0\/(\d+)\s+\d+\s+\d+\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = [
                'vlan' => $matches[1],
                'mac_address' => MacAddress::normalize($matches[2]),
                'type' => strtolower($matches[3]),
                'pon' => "0/{$matches[4]}",
            ];
        }

        return $results;
    }
}
