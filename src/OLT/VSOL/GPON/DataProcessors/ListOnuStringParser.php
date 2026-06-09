<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class ListOnuStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*GPON(\d+\/(\d+)):(\d+)\s+(\S+)\s+(\S+)\s+'
                . '(sn|loid|pw|hpw|sn\+pw|sn\+hpw|loid\+pw|loid\+hpw)\s+(\S+)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = (new Onu())
                ->setPon($matches[1])
                ->setId($matches[3])
                ->setModel($matches[4])
                ->setGponId(strtoupper($matches[7]));
        }

        return $results;
    }
}
