<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;

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
            if (str_starts_with($line, "%Error")) {
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