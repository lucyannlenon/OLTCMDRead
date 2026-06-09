<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\OnuStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\OnuStatus;
use LLENON\OltInformation\OLT\VSOL\EPON\Support\MacAddress;

final class OnuStatusCommand
{
    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly OnuStatusStringParser $parser = new OnuStatusStringParser()
    ) {
    }

    public function execute(string $macAddress): ?OnuStatus
    {
        $response = $this->connection->exec(
            'show onu status ' . MacAddress::forOnuLookup($macAddress)
        );
        $results = $response === false ? [] : $this->parser->parse($response);

        return $results[0] ?? null;
    }
}
