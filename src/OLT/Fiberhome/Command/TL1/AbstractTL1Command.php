<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\TL1;

abstract class AbstractTL1Command
{
    public function __construct(
        protected \LLENON\OltInformation\Connections\TL1Connection $conn)
    {
    }

    public abstract function exec(mixed $params = null):array;
}