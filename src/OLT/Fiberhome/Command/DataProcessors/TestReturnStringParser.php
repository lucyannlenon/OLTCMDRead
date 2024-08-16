<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class TestReturnStringParser  extends AbstractStringParser
{


    protected function localParse(string $input): array
    {
        dd($input);

        return $lines ?? [];
    }
}