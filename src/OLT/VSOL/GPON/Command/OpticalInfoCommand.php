<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Command;

use LLENON\OltInformation\OLT\VSOL\GPON\Connection\VSolGponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\GPON\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\GPON\Dto\OpticalInfo;

final class OpticalInfoCommand
{
    public function __construct(
        private readonly VSolGponConnectionInterface $connection,
        private readonly OpticalInfoStringParser $parser = new OpticalInfoStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): ?OpticalInfo
    {
        $response = $this->connection->execInPon(
            $pon,
            'show onu ' . self::validateOnuId($onuId) . ' optical_info'
        );
        $results = $response === false ? [] : $this->parser->parse($response);
        return $results[0] ?? null;
    }

    private static function validateOnuId(int $onuId): int
    {
        if ($onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('VSOL GPON ONU ID must be between 1 and 128.');
        }

        return $onuId;
    }
}
