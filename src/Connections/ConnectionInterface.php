<?php

namespace LLENON\OltInformation\Connections;

interface ConnectionInterface
{
    public function exec(string $cmd): mixed;

    public function setTimeout(int $timeout):void;
}