<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class TemperatureStringParser implements StringParserInterface
{
    public function parse(string $input): array
    {
        foreach (explode("\r\n", $input) as $line) {
            if (preg_match('/Temperature:\s*([\d.]+)\s*\(\s*([^\s)]+)\s*\)/', $line, $matches)) {
                return ["{$matches[1]}({$matches[2]})"];
            }
        }

        return [];
    }
}