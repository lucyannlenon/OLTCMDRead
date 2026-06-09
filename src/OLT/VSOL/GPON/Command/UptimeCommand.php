<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\UptimeStringParser;

final class UptimeCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly UptimeStringParser $parser = new UptimeStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): ?string
    {
        self::validateOnuId($onuId);
        $response = $this->connection->execInPon($pon, "show onu {$onuId} time-stamp");
        $results = $response === false ? [] : $this->parser->parse($response);
        return $results[0] ?? null;
    }

    private static function validateOnuId(int $onuId): void
    {
        if ($onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('VSOL GPON ONU ID must be between 1 and 128.');
        }
    }
}
