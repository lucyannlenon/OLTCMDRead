<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class ListOnuParse implements StringParserInterface
{

    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);
        $results = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($this->isLineValid($line)) {
                $results[] = $this->extractData($line);
            }
        }

        return $results;
    }

    private function isLineValid(string $line): bool
    {
        return !(str_contains($line, '---') || trim($line) === '' || str_contains($line, 'Itf') || str_starts_with($line, "     "));
    }

    private function extractData(string $line): ?Onu
    {
        if (preg_match('/^(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s*(.*)/', trim($line), $matches)) {
            $onu = new Onu();
            $onu->setPon($matches[1])
                ->setId($matches[2])
                ->setState($matches[4])
                ->setGponId($matches[3])
            ;
            return $onu;
        }
        return null;
    }


}