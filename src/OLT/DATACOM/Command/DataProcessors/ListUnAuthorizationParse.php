<?php

namespace LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class ListUnAuthorizationParse implements StringParserInterface
{

    public function parse(string $input): array
    {

        $lines = explode("\r\n", $input);
        $results = [];

        foreach ($lines as $line) {
            if ($this->isLineValid($line)) {

                $onu = $this->extractData($line);
                if ($onu) {
                    $results[] = $onu;
                }
            }
        }
        return $results;
    }

    private function isLineValid(string $line): bool
    {
        return !preg_match('/^\D/', trim($line));
    }

    private function extractData(string $line): ?Onu
    {
        if (preg_match('/^(\S+)\s+(\S+)/', trim($line), $matches)) {
            $onu = new Onu();
            $onu->setPon($matches[1])
                ->setGponId($matches[2]);
            return $onu;
        }
        return null;
    }
}