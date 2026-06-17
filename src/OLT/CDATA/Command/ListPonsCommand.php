<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\CDATA\Command;

use LLENON\OltInformation\Connections\ConnectionInterface;
use LLENON\OltInformation\OLT\Utils\PonList\RegisteredPonList;

final class ListPonsCommand
{
    private ListAllOnuCommand $listOnus;

    public function __construct(ConnectionInterface $connection)
    {
        $this->listOnus = new ListAllOnuCommand($connection);
    }

    /** @return list<string> */
    public function execute(): array
    {
        return RegisteredPonList::fromOnus($this->listOnus->execute());
    }
}
