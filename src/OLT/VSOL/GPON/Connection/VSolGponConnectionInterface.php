<?php

namespace LLENON\OltInformation\OLT\VSOL\GPON\Connection;

use LLENON\OltInformation\Connections\ConnectionInterface;

interface VSolGponConnectionInterface extends ConnectionInterface
{
    public function execInPon(int $pon, string $cmd): string|bool;

    public function disconnect(): void;
}
