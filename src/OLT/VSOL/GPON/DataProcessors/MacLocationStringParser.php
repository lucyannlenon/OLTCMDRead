<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\MacLocation;
use LLENON\OltInformation\OLT\VSOL\GPON\Support\MacAddress;

final class MacLocationStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        $values = [];

        foreach (preg_split('/\R/', $input) ?: [] as $line) {
            if (preg_match('/^\s*([^:]+):\s*(.*?)\s*$/', $line, $matches)) {
                $values[strtolower(trim($matches[1]))] = trim($matches[2]);
            }
        }

        foreach (['vlan', 'mac address', 'type', 'port'] as $key) {
            if (!isset($values[$key])) {
                return [];
            }
        }

        return [
            new MacLocation(
                MacAddress::normalize($values['mac address']),
                $values['vlan'],
                strtolower($values['type']),
                $values['port']
            ),
        ];
    }
}
