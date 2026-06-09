<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\EthernetStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\EthernetStatus;

final class EthernetStatusCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly EthernetStatusStringParser $parser = new EthernetStatusStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId, int $ethernetPort = 1): ?EthernetStatus
    {
        if ($onuId < 1 || $onuId > 128 || $ethernetPort < 1 || $ethernetPort > 32) {
            throw new \InvalidArgumentException('Invalid VSOL GPON ONU ID or Ethernet port.');
        }

        $response = $this->connection->execInPon(
            $pon,
            "show onu {$onuId} eth {$ethernetPort}"
        );
        $results = $response === false ? [] : $this->parser->parse($response);
        return $results[0] ?? null;
    }
}
