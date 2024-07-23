<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

class DistanceStringParser implements StringParserInterface
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
        return !(str_contains($line, '---') ||str_contains($line, '...') || trim($line) === '' || str_starts_with($line, "Eqd "));
    }

    private function extractData(string $line): string
    {
        return substr($line, 20);
    }
}