<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\OnuStatus;

final class OnuStatusStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (!preg_match(
                '/^\s*\d+\/\d+\/\d+:\d+\s+(\S+)\s+(\S+)\s+(\S+)\s*(.*)$/',
                $line,
                $matches
            )) {
                continue;
            }

            return [
                new OnuStatus(
                    strtolower($matches[1]),
                    strtolower($matches[2]),
                    strtolower($matches[3]),
                    trim($matches[4])
                ),
            ];
        }

        return [];
    }
}
