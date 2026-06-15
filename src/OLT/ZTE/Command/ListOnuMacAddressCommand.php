<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Dto\LearnedMacAddress;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\MacAddressTableStringParser;
use LLENON\OltInformation\OLT\ZTE\Dto\MacTableEntry;

final class ListOnuMacAddressCommand
{
    /** @var list<MacTableEntry>|null */
    private ?array $entries = null;

    public function __construct(
        private ConnectionInterface $connection,
        private MacAddressTableStringParser $parser = new MacAddressTableStringParser()
    ) {
    }

    /** @return list<LearnedMacAddress> */
    public function execute(string $pon, string $onuId): array
    {
        return array_values(array_map(
            static fn (MacTableEntry $entry): LearnedMacAddress => new LearnedMacAddress(
                $entry->macAddress,
                $entry->vlan,
                $entry->pon,
                $entry->onuId,
                $entry->type,
                uniPort: 'vport-' . $entry->pon . '.' . $entry->onuId . ':' . $entry->vport
            ),
            array_filter(
                $this->entries(),
                static fn (MacTableEntry $entry): bool => $entry->pon === $pon && $entry->onuId === $onuId
            )
        ));
    }

    /** @return list<MacTableEntry> */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $response = $this->connection->exec('show mac');
        return $this->entries = is_string($response) ? $this->parser->parse($response) : [];
    }
}
