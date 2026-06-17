<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\DATACOM\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Utils\PonList\RegisteredPonList;

final class ListPonsCommand
{
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {
    }

    /** @return list<string> */
    public function execute(): array
    {
        $response = $this->connection->exec('show running-config service-port | include gpon ');
        if (!is_string($response) || trim($response) === '') {
            return [];
        }

        preg_match_all('/gpon\s+(\d+\/\d+\/\d+)\s+onu\s+\d+/i', $response, $matches);
        return RegisteredPonList::normalize($matches[1] ?? []);
    }
}
