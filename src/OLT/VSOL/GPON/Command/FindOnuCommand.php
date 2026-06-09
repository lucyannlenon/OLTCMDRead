<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\FindOnuStringParser;

final class FindOnuCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly FindOnuStringParser $parser = new FindOnuStringParser()
    ) {
    }

    public function execute(string $serialNumber): ?Onu
    {
        $serialNumber = strtoupper(trim($serialNumber));

        if ($serialNumber === '' || preg_match('/^[A-Z0-9._:-]+$/', $serialNumber) !== 1) {
            throw new \InvalidArgumentException('Invalid VSOL GPON ONU serial number.');
        }

        $response = $this->connection->exec("onu search {$serialNumber}");
        $results = $response === false ? [] : $this->parser->parse($response);

        foreach ($results as $onu) {
            if ($onu->getGponId() === $serialNumber && strtolower($onu->getState()) === 'online') {
                return $onu;
            }
        }

        return $results[0] ?? null;
    }
}
