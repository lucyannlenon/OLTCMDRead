<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\EthernetStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\EthernetStatus;

final class EthernetStatusCommand
{
    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly EthernetStatusStringParser $parser = new EthernetStatusStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId, int $ethernetPort = 1): ?EthernetStatus
    {
        if (
            $pon < 1 || $pon > 4
            || $onuId < 1 || $onuId > 128
            || $ethernetPort < 1 || $ethernetPort > 32
        ) {
            throw new \InvalidArgumentException('Invalid VSOL EPON ONU address or Ethernet port.');
        }

        $prefix = "show onu {$onuId} ctc eth {$ethernetPort}";
        $responses = [];

        foreach (['port_info', 'autoneg', 'loopdetect'] as $detail) {
            $response = $this->connection->execInPon($pon, "{$prefix} {$detail}");
            if (is_string($response)) {
                $responses[] = $response;
            }
        }

        $results = $this->parser->parse(implode("\n", $responses));
        return $results[0] ?? null;
    }
}
