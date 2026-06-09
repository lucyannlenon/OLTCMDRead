<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class UptimeStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match(
                '/^\s*\d+\/\d+\s+'
                . '\d{4}:\d{2}:\d{2}\s+\d{2}:\d{2}:\d{2}\s+'
                . '\d{4}:\d{2}:\d{2}\s+\d{2}:\d{2}:\d{2}\s+'
                . '(\d+\s+\d{2}:\d{2}:\d{2})\s*$/',
                $line,
                $matches
            )) {
                return [$matches[1]];
            }
        }

        return [];
    }
}
