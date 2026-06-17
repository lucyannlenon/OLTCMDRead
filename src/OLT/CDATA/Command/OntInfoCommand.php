<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\OLT\CDATA\CDATAConnection;
use LLENON\OltInformation\OLT\CDATA\DataProcessors\OntInfoStringParser;
use LLENON\OltInformation\OLT\CDATA\Dto\OntInfo;
use LLENON\OltInformation\OLT\CDATA\Support\PonAddress;

/**
 * Reads ONU status/distance/uptime via "show ont info <frame/slot> <port> <onu-id>".
 * Unlike the optical command, this one works in the absolute form in (config)#
 * mode, so no "interface epon" context switch is needed.
 */
final class OntInfoCommand
{
    public function __construct(
        private readonly CDATAConnection $connection,
        private readonly OntInfoStringParser $parser = new OntInfoStringParser()
    ) {
        $this->connection->setTimeout(10);
    }

    public function execute(string $pon, int $onuId): ?OntInfo
    {
        if ($onuId < 1 || $onuId > 64) {
            throw new \InvalidArgumentException('CDATA ONU ID must be between 1 and 64.');
        }

        $address = PonAddress::fromString($pon);
        $response = $this->connection->exec(
            "show ont info {$address->frameSlot()} {$address->port} {$onuId}"
        );
        $results = $response === false ? [] : $this->parser->parse($response);

        return $results[0] ?? null;
    }
}
