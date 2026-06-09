<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\OpticalInfo;

final class OpticalInfoStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*EPON0\/\d+:\d+\s+'
                . '(-?\d+(?:\.\d+)?)\s+'
                . '(-?\d+(?:\.\d+)?)\s+'
                . '(-?\d+(?:\.\d+)?)\s+'
                . '(-?\d+(?:\.\d+)?)\s+'
                . '(-?\d+(?:\.\d+)?)\s*$/i',
                $line,
                $matches
            )) {
                continue;
            }

            $results[] = new OpticalInfo(
                $matches[1],
                $matches[2],
                $matches[3],
                $matches[4],
                $matches[5]
            );
        }

        return $results;
    }
}
