<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\ZTE\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class ListOnuCommand  extends AbstractCommand
{
    protected string $pon;
    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new ListOnuStringParser());
    }

    public function execute(string $pon): mixed
    {
        $this->pon = $pon;
       return $this->exec();

    }

    protected function getCommand(): string
    {
        return "show pon onu information gpon_olt-{$this->pon}";
    }

}