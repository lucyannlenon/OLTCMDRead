<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\LearnedMacAddressStringParser;

final class ListOnuMacAddressCommand
{
    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly LearnedMacAddressStringParser $parser = new LearnedMacAddressStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): array
    {
        if ($pon < 1 || $pon > 4 || $onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('Invalid VSOL EPON PON or ONU ID.');
        }

        $response = $this->connection->execInPon(
            $pon,
            "show onu {$onuId} mac-address-table"
        );

        return $response === false ? [] : $this->parser->parse($response);
    }
}
