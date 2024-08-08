<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class DetailInfoStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return array
     */
    public function parse(string $input): array
    {
        $regexs = [
            'signal' => 'Rx Optical Power \[dBm\]\s+?:(?P<signal>.*)',
            'uptime' => 'Uptime\s+?:(?P<uptime>.*)',
            'distance' => 'Distance\s+?:(?P<distance>.*)',
            'status' => 'Operational state\s+?:(?P<status>.*)',
            'tx_power' => 'Tx Optical Power \[dBm\]\s+?:(?P<tx_power>.*)',
            'anti_rogue' => 'Anti Rogue ONU isolate\s+?:(?P<anti_rogue>.*)'
        ];

        $data = [];
        foreach ($regexs as $k => $regex) {
            if (preg_match('/' . $regex . '/', $input, $matches)) {
                $data[$k] = $matches[$k] ? trim($matches[$k]): 'Not found';
            }
        }

        return $data;
    }


}