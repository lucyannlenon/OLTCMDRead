<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class DetailInfoStringParser implements StringParserInterface
{

    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);

        $results = [];

        foreach ($lines as $line) {
            if ($this->isLineValid($line)) {
                $results['logs'][] = $this->extractData($line);

            }
        }
        return $results;
    }

    private function isLineValid(string $line): bool
    {
        return !(str_contains($line, '---') || trim($line) === '' || !preg_match('/^\d/', trim($line)) || str_starts_with(trim($line), '1PPS'));
    }

    private function extractData(string $line): array
    {

        return [
            'start'=> trim(substr($line,7,20)),
            'end'=> trim(substr($line,29,20)),
            'cause'=> @trim(@substr($line,52))
        ];
    }
}