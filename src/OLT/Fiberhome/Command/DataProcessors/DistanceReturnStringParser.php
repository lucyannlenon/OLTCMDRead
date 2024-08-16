<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class DistanceReturnStringParser extends AbstractStringParser
{


    protected function localParse(string $input): array
    {
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            if ($this->isLineValid($line)) {
                $item = explode("\t", $line);
                return [(int) $item[count($item) - 1]];
            }
        }

        return [];
    }

    private function isLineValid(string $line): bool
    {
        return preg_match('/^\d/', $line);
    }
}