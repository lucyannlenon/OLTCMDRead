<?php

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;

class ListAllOnuCommand
{
    private ListOnuCommand $listOnu;

    public function __construct(ConnectionInterface $connection)
    {
        $this->listOnu = new ListOnuCommand($connection);
    }

    public function execute(): array
    {
        $onus = [];

        for ($port = 1; $port <= 8; $port++) {
            array_push($onus, ...$this->listOnu->execute((string) $port));
        }

        return $onus;
    }
}
