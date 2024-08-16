<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Dto\OnuEthernet;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class EtherReturnStringParser extends AbstractStringParser
{


    protected function localParse(string $input): array
    {
        $lines = explode("\n", $input);
        $return = [];
        foreach ($lines as $line) {
            if ($this->isLineValid($line)) {
                $item = explode("\t", $line);
                if (count($item) > 5) {
                    $return[] = new OnuEthernet("Ether" . count($return), $item[count($item) - 1]);
                }
            }
        }

        return $return;
    }

    private function isLineValid(string $line): bool
    {
        return preg_match('/^UP/', $line);
    }
}