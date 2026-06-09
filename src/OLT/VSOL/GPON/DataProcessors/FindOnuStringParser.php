<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class FindOnuStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*pon\s+(\d+)\s+onu\s+(\d+)\s+sn\s+(\S+)\s+(Online|Offline)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = (new Onu())
                ->setPon("0/{$matches[1]}")
                ->setId($matches[2])
                ->setGponId(strtoupper($matches[3]))
                ->setState(strtolower($matches[4]));
        }

        return $results;
    }
}
