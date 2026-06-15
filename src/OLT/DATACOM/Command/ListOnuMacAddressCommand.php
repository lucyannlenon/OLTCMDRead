<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\DATACOM\Command\DataProcessors\MacAddressTableStringParser;
use LLENON\OltInformation\OLT\DATACOM\Dto\MacTableEntry;
use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;

final class ListOnuMacAddressCommand
{
    private GetServicePortCommand $servicePort;
    /** @var list<MacTableEntry>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly MacAddressTableStringParser $parser = new MacAddressTableStringParser()
    ) {
        $this->servicePort = new GetServicePortCommand($connection);
    }

    /** @return list<LearnedMacAddress> */
    public function execute(string $pon, string $onuId): array
    {
        $servicePort = $this->servicePort->execute($pon, $onuId);
        if ($servicePort === null) {
            return [];
        }

        return array_values(array_map(
            static fn (MacTableEntry $entry): LearnedMacAddress => new LearnedMacAddress(
                $entry->macAddress,
                $entry->vlan,
                $pon,
                $onuId,
                $entry->type,
                uniPort: 'service-port-' . $entry->servicePort
            ),
            array_filter(
                $this->entries(),
                static fn (MacTableEntry $entry): bool => $entry->servicePort === $servicePort
            )
        ));
    }

    /** @return list<MacTableEntry> */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $response = $this->connection->exec('show mac-address-table');
        return $this->entries = is_string($response) ? $this->parser->parse($response) : [];
    }
}
