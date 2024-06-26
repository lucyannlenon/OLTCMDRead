<?php

namespace LLENON\OltInformation\OLT\ZTE\Command;

use LLENON\OltInformation\OLT\Dto\Onu;
use LLENON\OltInformation\OLT\ZTE\DataProcessors\ListOnuStringParser;
use LLENON\OltInformation\OLT\ZTE\ZTEConnection;

class NextIdCommand extends AbstractCommand
{
    public function __construct(ZTEConnection $connection)
    {
        parent::__construct($connection, new ListOnuStringParser());
    }


    private string $pon;

    public function execute(string $pon): int
    {
        $this->pon = $pon;
        $ret = $this->exec();
        $i = 1;
        /** @var Onu $item */
        foreach ($ret as $item) {
            if ($item->getId() != $i) {
                return $i;
            }
            $i++;
        }
        if ($i > 128) {
            throw new \Exception("PON has reached its maximum capacity of 128 ONUs.");
        }
        return $i;
    }

    public function getCommand(): string
    {
        return "show pon onu information gpon_olt-{$this->pon}";
    }
}