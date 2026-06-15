<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\ZTE\Dto\MacTableEntry;

final class MacAddressTableStringParser implements StringParserInterface
{
    /** @return list<MacTableEntry> */
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match(
                '/^\s*([0-9A-F]{4}(?:[.:][0-9A-F]{4}){2}|[0-9A-F]{2}(?::[0-9A-F]{2}){5})'
                . '\s+(\S+)\s+(\S+)\s+vport-(\d+\/\d+\/\d+)\.(\d+):(\d+)\s*$/i',
                $line,
                $matches
            ) !== 1) {
                continue;
            }

            $results[] = new MacTableEntry(
                $matches[1],
                $matches[2],
                strtolower($matches[3]),
                $matches[4],
                $matches[5],
                $matches[6]
            );
        }

        return $results;
    }
}
