<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\VSOL\EPON\Command;

use LLENON\OltInformation\OLT\Utils\PonList\RegisteredPonList;
use LLENON\OltInformation\OLT\VSOL\EPON\Connection\VSolEponConnectionInterface;

final class ListPonsCommand
{
    private ListOnuCommand $listOnus;

    public function __construct(VSolEponConnectionInterface $connection)
    {
        $this->listOnus = new ListOnuCommand($connection);
    }

    /** @return list<string> */
    public function execute(): array
    {
        return RegisteredPonList::fromOnus($this->listOnus->execute());
    }
}
