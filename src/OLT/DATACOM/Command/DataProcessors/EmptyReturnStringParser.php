<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class EmptyReturnStringParser implements StringParserInterface
{
    /**
     * @param string $input
     * @return array
     * @throws OltCommandException
     */
    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);
        foreach ($lines as $line) {
            if (str_contains($line, "error:")) {
                $this->handlerException($input, $line);
            }
        }

        return $lines ?? [];
    }

    private function handlerException(string $cause, string $error): void
    {
        throw  new OltCommandException($error, $cause);
    }

}