<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class ListUnAuthorizedStringParser implements  StringParserInterface
{

    public function parse(string $input): array
    {

        $lines = explode("\n", $input);
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
        return preg_match('/^\d/',$line);
    }

    private function extractData(string $line): ?Onu
    {
        $items = explode("\t", $line);
        if(!empty($items)){

            $onu = new Onu();
            $onu->setPon("NA-NA-{$items[0]}-{$items[1]}")
                ->setModel($items[7])
                ->setGponId($items[2]);
            return $onu;
        }
        return null;
    }
}