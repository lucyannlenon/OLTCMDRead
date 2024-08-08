<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class EtherReturnStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return OnuEthernet[]
     */
    public function parse(string $input): array
    {

        // Regular expression to capture the interface name and speed
        preg_match_all('/Physical interface\s+:\s+([a-z0-9 ]+),.*?Status Negotiation:\s+([0-9]+ (Mbit|Gbit)\/s)/is', $input, $matches);

        // Associative array to store the results
        $result = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $interface = trim($matches[1][$i]);
            $speed = trim($matches[2][$i]);
            $result[] = new OnuEthernet($interface, $speed);
        }


        return $result;
    }

}