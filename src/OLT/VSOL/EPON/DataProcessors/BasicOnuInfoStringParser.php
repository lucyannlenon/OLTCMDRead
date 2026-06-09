<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class BasicOnuInfoStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*EPON0\/(\d+):(\d+)\s+(\S+)\s+(\S+)\s+([0-9A-F]{12})\s+(\S+)\s+(\S+)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = (new Onu())
                ->setPon("0/{$matches[1]}")
                ->setId($matches[2])
                ->setModel("{$matches[3]} {$matches[4]}")
                ->setGponId(MacAddress::normalize($matches[5]))
                ->setState('');
        }

        return $results;
    }
}
