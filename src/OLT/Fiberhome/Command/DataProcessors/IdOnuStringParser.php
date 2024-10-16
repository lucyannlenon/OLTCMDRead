<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

class IdOnuStringParser implements StringParserInterface
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

        return !empty($results) ? [$results[0]->getId()] : [];
    }

    private function isLineValid(string $line): bool
    {
        return preg_match('/^\d/', $line);
    }

    private function extractData(string $line): ?Onu
    {
        $matches = explode("\t", $line);
        if (!empty($matches)) {
            $onu = new Onu();
            $onu->setId($matches[0])
                ->setState($matches[2])
                ->setGponId($matches[4]);
            return $onu;
        }
        return null;
    }
}