<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\MacAddressTableStringParser;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\ServicePortLocationStringParser;
use LLENON\OltInformation\OLT\Dto\MacLocation;

final readonly class LocateMacAddressCommand
{
    public function __construct(
        private ConnectionInterface $connection,
        private MacAddressTableStringParser $macParser = new MacAddressTableStringParser(),
        private ServicePortLocationStringParser $locationParser = new ServicePortLocationStringParser()
    ) {
    }

    public function execute(string $macAddress): ?MacLocation
    {
        $normalized = MacLocation::normalizeMacAddress($macAddress);
        $response = $this->connection->exec("show mac-address-table mac-address {$normalized}");
        if (!is_string($response)) {
            return null;
        }

        $entry = $this->macParser->parse($response)[0] ?? null;
        if ($entry === null) {
            return null;
        }

        $config = $this->connection->exec("show running-config service-port {$entry->servicePort}");
        if (!is_string($config)) {
            return null;
        }

        $location = $this->locationParser->parse($config)[0] ?? null;
        if ($location === null) {
            return null;
        }

        return new MacLocation(
            $entry->macAddress,
            $location['pon'],
            $location['onuId'],
            $entry->vlan,
            $entry->type,
            'service-port-' . $entry->servicePort
        );
    }
}
