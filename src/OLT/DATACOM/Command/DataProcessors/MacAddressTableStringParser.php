<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\OLT\DATACOM\Dto\MacTableEntry;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class MacAddressTableStringParser implements StringParserInterface
{
    /** @return list<MacTableEntry> */
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match(
                '/^\s*service-port-(\d+)\s+([0-9A-F]{2}(?::[0-9A-F]{2}){5})\s+(\S+)\s+(\S+)\s*$/i',
                $line,
                $matches
            ) !== 1) {
                continue;
            }

            $results[] = new MacTableEntry(
                (int) $matches[1],
                $matches[2],
                $matches[3],
                strtolower($matches[4])
            );
        }

        return $results;
    }
}
