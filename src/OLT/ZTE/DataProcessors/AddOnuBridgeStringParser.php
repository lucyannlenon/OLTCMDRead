<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\StringParserInterface;

class AddOnuBridgeStringParser implements StringParserInterface
{

    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);

        return $lines ?? [];
    }

}