<?php

namespace LLENON\OltInformation\Connections;

interface ConnectionInterface
{
    public function exec(string $cmd): mixed;

}