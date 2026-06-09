<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class DistanceStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        return preg_match('/Distance:\s*(\d+)m/i', $input, $matches)
            ? [$matches[1]]
            : [];
    }
}
