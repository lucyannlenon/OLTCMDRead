<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\StringParserInterface;

class EthernetReturnStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return array
     * @throws OltCommandException
     */
    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);
        $name = "";
        $speed = "";

        $items = [];
        foreach ($lines as $line) {
            if(!$this->isLineValid($line)){
                continue;
            }
            list($item, $value) = explode(":", $line);

            if (trim($item) == "Interface") {
                $name = trim($value);
            } elseif (trim($item) == "Speed status") {
                $speed = trim($value);
            } elseif (trim($item) == "Status changes") {
                $items[] = new OnuEthernet($name, $speed);
            }
        }

        return $items;
    }
    private function isLineValid(string $line): bool
    {
        return str_contains($line, ':');
    }

}