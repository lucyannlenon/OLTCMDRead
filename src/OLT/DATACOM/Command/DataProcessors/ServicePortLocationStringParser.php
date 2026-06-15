<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

final class ServicePortLocationStringParser implements StringParserInterface
{
    /** @return list<array{pon:string,onuId:string}> */
    public function parse(string $input): array
    {
        if (preg_match('/\bgpon\s+(\S+)\s+onu\s+(\d+)\b/i', $input, $matches) !== 1) {
            return [];
        }

        return [[
            'pon' => $matches[1],
            'onuId' => $matches[2],
        ]];
    }
}
