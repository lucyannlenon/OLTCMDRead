<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\OnuStatusStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\OnuStatus;

final class OnuStatusCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly OnuStatusStringParser $parser = new OnuStatusStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): ?OnuStatus
    {
        self::validateAddress($pon, $onuId);
        $response = $this->connection->exec("show onu state {$pon} {$onuId}");
        $results = $response === false ? [] : $this->parser->parse($response);
        return $results[0] ?? null;
    }

    private static function validateAddress(int $pon, int $onuId): void
    {
        if ($pon < 1 || $pon > 8 || $onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('Invalid VSOL GPON PON or ONU ID.');
        }
    }
}
