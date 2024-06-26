<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;

class ListUnAuthorizedStringParser implements StringParserInterface
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
        return !(str_contains($line, '---') || trim($line) === '' || str_contains($line, 'OltIndex'));
    }

    private function extractData(string $line): ?Onu
    {
        if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', trim($line), $matches)) {
            $onu = new Onu();
            $onu->setPon(str_replace("gpon_olt-", '', $matches[1]))
                ->setModel($matches[2])
                ->setGponId($matches[3]);
            return $onu;
        }
        return null;
    }
}