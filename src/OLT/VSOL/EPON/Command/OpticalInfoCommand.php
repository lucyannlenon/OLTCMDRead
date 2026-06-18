<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;
use LLENON\OltInformation\OLT\VSOL\EPON\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\VSOL\EPON\Dto\OpticalInfo;

final class OpticalInfoCommand
{
    public function __construct(
        private readonly VSolEponConnectionInterface $connection,
        private readonly OpticalInfoStringParser $parser = new OpticalInfoStringParser()
    ) {
    }

    public function execute(int $pon, int $onuId): ?OpticalInfo
    {
        self::validateAddress($pon, $onuId);
        $response = $this->connection->exec("show onu opm-diag pon {$pon},{$onuId}");
        $results = $response === false ? [] : $this->parser->parse($response);

        foreach ($results as $result) {
            if ($result->pon === $pon && $result->onuId === $onuId) {
                return $result;
            }
        }

        return null;
    }

    private static function validateAddress(int $pon, int $onuId): void
    {
        if ($pon < 1 || $pon > 4 || $onuId < 1 || $onuId > 128) {
            throw new \InvalidArgumentException('Invalid VSOL EPON PON or ONU ID.');
        }
    }
}
