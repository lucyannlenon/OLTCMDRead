<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use InvalidArgumentException;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\StringParserInterface;

class SignalStringParser implements StringParserInterface
{

    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);
        $results = [];

        foreach ($lines as $line) {
            if ($this->isLineValid($line)) {
                $results[] = $this->extractData($line);
            }
        }


        return $results;
    }

    private function isLineValid(string $line): bool
    {
        return !(str_contains($line, '---') || trim($line) === '' || str_starts_with($line, "Onu"));
    }

    private function extractData(string $line): string
    {
        return substr($line, 21);
    }
}