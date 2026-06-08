<?php

namespace LLENON\OltInformation\OLT\CDATA\DataProcessors;

use LLENON\OltInformation\OLT\CDATA\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class LearnedMacAddressStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];
        $lines = preg_split('/\R/', $input) ?: [];

        foreach ($lines as $line) {
            if (!preg_match(
                '/^\s*([0-9A-F]{2}(?::[0-9A-F]{2}){5})\s+'
                . '(\S+)\s+pon(\d+\/\d+\/[1-8])\s+(\d+)\s+(\S+)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = new LearnedMacAddress(
                strtoupper($matches[1]),
                $matches[2],
                $matches[3],
                $matches[4],
                strtolower($matches[5])
            );
        }

        return $results;
    }
}
