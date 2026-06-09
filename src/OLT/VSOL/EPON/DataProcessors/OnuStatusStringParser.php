<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\OnuStatus;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class OnuStatusStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $results = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            $columns = preg_split('/\s{2,}/', trim($line)) ?: [];
            if (
                count($columns) < 9
                || preg_match('/^EPON0\/(\d+):(\d+)$/i', $columns[0], $address) !== 1
            ) {
                continue;
            }

            try {
                $macAddress = MacAddress::normalize($columns[2]);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $results[] = new OnuStatus(
                "0/{$address[1]}",
                (int) $address[2],
                strtolower($columns[1]),
                $macAddress,
                $columns[3],
                $columns[count($columns) - 2]
            );
        }

        return $results;
    }
}
