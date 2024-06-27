<?php

namespace LLENON\OltInformation\OLT\ZTE\DataProcessors;

use InvalidArgumentException;
use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\StringParserInterface;

class ListOnuStringParser implements StringParserInterface
{

    public function parse(string $input): array
    {
        $lines = explode("\r\n", $input);
        unset($lines[0]);
        unset($lines[1]);
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
        return !(str_contains($line, '---') || trim($line) === '' || str_contains($line, 'OltIndex') || str_starts_with($line, "     "));
    }

    private function extractData(string $line): ?Onu
    {
        if (preg_match('/^(\S+):(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', trim($line), $matches)) {
            $onu = new Onu();
            $onu->setPon($matches[1])
                ->setId($matches[2])
                ->setState($matches[6])
                ->setOfflineTimes($matches[7])
                ->setGponId($this->getGponId($matches[5]));
            return $onu;
        }
        return null;
    }

    private function getGponId(string $sn): string
    {
        if (preg_match('/^SN\((\S+)\)$/', $sn, $matches)) {
            return $matches[1];
        }
        throw  new InvalidArgumentException('Cannot get onuId from ' . $sn);
    }
}