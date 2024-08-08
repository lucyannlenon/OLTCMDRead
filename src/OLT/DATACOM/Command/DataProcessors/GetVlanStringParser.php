<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class GetVlanStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return array
     */
    public function parse(string $input): array
    {
        // Regular expression to capture the VLAN ID
        preg_match('/vlan-id\s+(\d+)/', $input, $matches);
        if (isset($matches[1])) {
            return [$matches[1]];
        }
        return [];
    }



}