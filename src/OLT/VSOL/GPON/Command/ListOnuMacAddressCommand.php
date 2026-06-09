<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\LearnedMacAddressStringParser;

final class ListOnuMacAddressCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly LearnedMacAddressStringParser $parser = new LearnedMacAddressStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): array
    {
        if ($pon < 1 || $pon > 8 || $onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('Invalid VSOL GPON PON or ONU ID.');
        }

        $response = $this->connection->exec("show mac address-table pon {$pon} {$onuId}");
        return $response === false ? [] : $this->parser->parse($response);
    }
}
