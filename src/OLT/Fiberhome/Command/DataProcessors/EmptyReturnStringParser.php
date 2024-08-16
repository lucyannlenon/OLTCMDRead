<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class EmptyReturnStringParser extends AbstractStringParser
{
    protected function localParse(string $input): array
    {
        return [];
    }


}