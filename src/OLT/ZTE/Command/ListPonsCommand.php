<?php

declare(strict_types=1);

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Utils\PonList\RegisteredPonList;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

final class ListPonsCommand
{
    private ListAllOnuCommand $listOnus;

    public function __construct(ZTEConnection $connection)
    {
        $this->listOnus = new ListAllOnuCommand($connection);
    }

    /** @return list<string> */
    public function execute(): array
    {
        $pons = [];

        foreach ($this->listOnus->execute() as $onu) {
            $pon = trim((string) $onu->getPon());
            if ($pon === '') {
                continue;
            }

            $pons[] = preg_replace('/^[a-z-]+_/i', '', $pon) ?? $pon;
        }

        return RegisteredPonList::normalize($pons);
    }
}
