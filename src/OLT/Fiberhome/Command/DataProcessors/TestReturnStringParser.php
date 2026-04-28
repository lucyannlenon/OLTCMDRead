<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class TestReturnStringParser  extends AbstractStringParser
{


    protected function localParse(string $input): array
    {
        $lines = preg_split("/\\R/", trim($input)) ?: [];
        return array_values(array_filter(array_map('trim', $lines), static fn($line) => $line !== ''));
    }
}
