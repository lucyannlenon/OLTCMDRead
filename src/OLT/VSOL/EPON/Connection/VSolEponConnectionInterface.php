<?php

namespace LLENON\OltInformation\OLT\VSOL\EPON\Connection;

use LLENON\OltInformation\Connections\ConnectionInterface;

interface VSolEponConnectionInterface extends ConnectionInterface
{
    public function execInPon(int $pon, string $cmd): string|bool;

    public function disconnect(): void;
}
