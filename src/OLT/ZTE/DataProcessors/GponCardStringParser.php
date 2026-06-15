<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class GponCardStringParser implements StringParserInterface
{
    /** @return list<array{shelf:int,slot:int,ports:int,card:string}> */
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match(
                '/^\s*(\d+)\s+(\d+)\s+(GV\w+)\s+\S+\s+(\d+)\s+\S+\s+INSERVICE\s*$/i',
                $line,
                $matches
            ) !== 1) {
                continue;
            }

            $results[] = [
                'shelf' => (int) $matches[1],
                'slot' => (int) $matches[2],
                'ports' => (int) $matches[4],
                'card' => strtoupper($matches[3]),
            ];
        }

        return $results;
    }
}
