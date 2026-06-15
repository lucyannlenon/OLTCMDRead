<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Dto\MacLocation;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\MacAddressTableStringParser;

final readonly class LocateMacAddressCommand
{
    public function __construct(
        private ConnectionInterface $connection,
        private MacAddressTableStringParser $parser = new MacAddressTableStringParser()
    ) {
    }

    public function execute(string $macAddress): ?MacLocation
    {
        $normalized = MacLocation::normalizeMacAddress($macAddress);
        $response = $this->connection->exec('show mac');
        if (!is_string($response)) {
            return null;
        }

        foreach ($this->parser->parse($response) as $entry) {
            if ($entry->macAddress !== $normalized) {
                continue;
            }

            return new MacLocation(
                $entry->macAddress,
                $entry->pon,
                $entry->onuId,
                $entry->vlan,
                $entry->type,
                'vport-' . $entry->pon . '.' . $entry->onuId . ':' . $entry->vport
            );
        }

        return null;
    }
}
