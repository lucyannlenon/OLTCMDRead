<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class GetServicePortStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return array
     * @throws OltCommandException
     */
    public function parse(string $input): array
    {
        preg_match('/service-port (\d+)/', $input, $matches);

        if (isset($matches[1])) {
            return [$matches[1]];
        }
        return [];
    }

    private function handlerException(string $cause, string $error): void
    {
        throw  new OltCommandException($error, $cause);
    }

}