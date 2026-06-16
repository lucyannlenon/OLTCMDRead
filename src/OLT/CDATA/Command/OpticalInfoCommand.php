<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\OpticalInfoStringParser;
use LLENON\OltInformation\OLT\CDATA\Dto\OpticalInfo;
use LLENON\OltInformation\OLT\CDATA\Support\PonAddress;

final class OpticalInfoCommand
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly OpticalInfoStringParser $parser = new OpticalInfoStringParser()
    ) {
        $this->connection->setTimeout(10);
    }

    public function execute(string $pon, int $onuId): ?OpticalInfo
    {
        if ($onuId < 1 || $onuId > 64) {
            throw new \InvalidArgumentException('CDATA ONU ID must be between 1 and 64.');
        }

        $address = PonAddress::fromString($pon);
        $response = $this->connection->exec(
            "show ont optical-info {$address->frameSlot()} {$address->port} {$onuId}"
        );
        $results = $response === false ? [] : $this->parser->parse($response);

        return $results[0] ?? null;
    }
}
