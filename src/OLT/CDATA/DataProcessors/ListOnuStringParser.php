<?php

namespace LLENON\OltInformation\OLT\CDATA\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class ListOnuStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];
        $lines = preg_split('/\R/', $input) ?: [];

        foreach ($lines as $line) {
            if (!preg_match(
                '/^\s*(\d+\/\d+)\s+(\d+)\s+(\d+)\s+'
                . '([0-9A-F]{2}(?::[0-9A-F]{2}){5})\s+'
                . '(\S+)\s+(\S+)\s+(\S+)\s+(\S+)(?:\s+.*)?$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $onu = new Onu();
            $onu->setPon("{$matches[1]}/{$matches[2]}")
                ->setId($matches[3])
                ->setGponId(strtoupper($matches[4]))
                ->setState(strtolower($matches[6]));
            $results[] = $onu;
        }

        return $results;
    }
}
